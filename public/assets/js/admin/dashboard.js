import {adminFragmentLoader} from "../fragmentLoader";

// Maintenance Mode Toggle
function toggle_maintenance_mode(turn_on = true) {
	const confirmtext = turn_on ? "Wartungsmodus aktivieren?" : "Wartungsmodus deaktivieren?";
	let continu = confirm(confirmtext);
	if (!continu) {
		// Revert checkbox state
		const checkbox = document.getElementById('maintenance-mode-toggle');
		if (checkbox) {
			checkbox.checked = !turn_on;
		}
		return;
	}

	const card = document.querySelector('.maintenance-card');
	const iconDiv = card?.querySelector('.card-icon .material-symbol');
	const label = card?.querySelector('.toggle-label');
	const turn = turn_on ? "on" : "off";

	// Update UI
	if (turn_on) {
		card?.classList.add('maintenance-active');
		if (label) label.textContent = 'Aktiv';
		if (iconDiv) {
			fetch('/assets/icons/material/build.svg')
				.then(response => response.text())
				.then(svg => iconDiv.innerHTML = svg)
				.catch(e => console.error('Failed to load icon:', e));
		}
	} else {
		card?.classList.remove('maintenance-active');
		if (label) label.textContent = 'Inaktiv';
		if (iconDiv) {
			fetch('/assets/icons/material/build_circle.svg')
				.then(response => response.text())
				.then(svg => iconDiv.innerHTML = svg)
				.catch(e => console.error('Failed to load icon:', e));
		}
	}

	fetch(`/admin/api/maintenance/${turn}`, {method: "PUT"})
		.catch(e => console.error(e));
}

$(document).on("change", "#maintenance-mode-toggle", function () {
	let turn_on = $(this).prop('checked');
	toggle_maintenance_mode(turn_on);
});

// Click auf die Maintenance Card togglet den Switch
$(document).on("click", ".dashboard-card.maintenance-card", function (e) {
	// Verhindere, dass der Click auf dem Toggle selbst doppelt ausgelöst wird
	if ($(e.target).closest('.toggle-switch').length > 0) {
		return;
	}

	const checkbox = document.getElementById('maintenance-mode-toggle');
	if (checkbox) {
		// Toggle und trigger change event
		checkbox.checked = !checkbox.checked;
		$(checkbox).trigger('change');
	}
});

$(document).on("keydown", "div[role='button'].dashboard-card", function (e) {
	if (e.key === 'Enter' || e.key === ' ') {
		e.preventDefault();
		$(this).trigger('click');
	}
});

$(document).on('click', 'a.dashboard-card', function () {
	$(this).append('<div class="content-loading-indicator" style="position: absolute"></div>');
});
window.addEventListener('pageshow', function() {
	document.querySelectorAll('a.dashboard-card .content-loading-indicator').forEach(el => el.remove());
});
window.addEventListener('load', function() {
	document.querySelectorAll('a.dashboard-card .content-loading-indicator').forEach(el => el.remove());
});


// Click on suggestion item to show details
$(document).on('click', '.suggestion-item', async function() {
	const matchupId = $(this).data('matchup-id');
	const dialogContent = $(this).closest('.dialog-content');
	const queryAddition = $(this).hasClass('accepted') ? '&oplCompare=true' : '';

	// Add loading indicator
	const loadingIndicator = $('<div class="content-loading-indicator"></div>');
	dialogContent.append(loadingIndicator);

	try {
		const content = await adminFragmentLoader(`admin-suggestion-details?matchupId=${matchupId}${queryAddition}`);
		dialogContent.html(content);
	} catch (error) {
		console.error('Error loading suggestion details:', error);
		loadingIndicator.remove();
	}
});

// Back button to suggestions list
$(document).on('click', '#back-to-suggestions-list', async function() {
	const dialogContent = $(this).closest('.dialog-content');
	const toList = $(this).data('to');

	const loadingIndicator = $('<div class="content-loading-indicator"></div>');
	dialogContent.append(loadingIndicator);

	try {
		const content = await adminFragmentLoader(`admin-suggestions-popup-content?openTab=${toList}`);
		dialogContent.html(content);
	} catch (error) {
		console.error('Error loading suggestions list:', error);
		loadingIndicator.remove();
	}
});

// Accept suggestion in admin view
$(document).on('click', 'button.accept-suggestion-admin', async function() {
	const suggestionId = $(this).data('suggestion-id');
	const matchupId = $(this).data('matchup-id');
	const dialogContent = $(this).closest('.dialog-content');

	const loadingIndicator = $('<div class="content-loading-indicator"></div>');
	dialogContent.append(loadingIndicator);

	try {
		const response = await fetch(`/admin/api/suggestions/${suggestionId}/accept`, {method: 'POST'});

		if (!response.ok) {
			throw new Error('Fehler beim Akzeptieren des Vorschlags');
		}

		const result = await response.json();

		if (result.error) {
			alert(result.error);
			loadingIndicator.remove();
			return;
		}

		// Reload the details or go back to list
		const content = await adminFragmentLoader(`admin-suggestion-details?matchupId=${matchupId}`);
		dialogContent.html(content);
		updateSuggestionCard();
	} catch (error) {
		console.error('Error accepting suggestion:', error);
		alert('Fehler beim Akzeptieren des Vorschlags');
		loadingIndicator.remove();
	}
});

// Reject suggestion in admin view
$(document).on('click', 'button.reject-suggestion-admin', async function() {
	const suggestionId = $(this).data('suggestion-id');
	const matchupId = $(this).data('matchup-id');
	const dialogContent = $(this).closest('.dialog-content');

	const loadingIndicator = $('<div class="content-loading-indicator"></div>');
	dialogContent.append(loadingIndicator);

	try {
		const response = await fetch(`/admin/api/suggestions/${suggestionId}/reject`, {method: 'POST'});

		if (!response.ok) {
			throw new Error('Fehler beim Ablehnen des Vorschlags');
		}

		const result = await response.json();

		if (result.error) {
			alert(result.error);
			loadingIndicator.remove();
			return;
		}

		// Reload the details or go back to list
		const content = await adminFragmentLoader(`admin-suggestion-details?matchupId=${matchupId}`);
		dialogContent.html(content);
		updateSuggestionCard();
	} catch (error) {
		console.error('Error rejecting suggestion:', error);
		alert('Fehler beim Ablehnen des Vorschlags');
		loadingIndicator.remove();
	}
});

function updateSuggestionCard() {
	fetch(`/admin/api/suggestions/amount`)
		.then(res => {
			if (res.ok) {
				res.text().then(amount => {
					const card = $('div.suggestions-card');
					const stat = card.find('.card-stats .stat:first-child');
					stat.find('.stat-value').text(amount);
					if (amount === '0') {
						card.removeClass('has-suggestions');
						stat.removeClass('highlight');
					} else {
						card.addClass('has-suggestions');
						stat.addClass('highlight');
					}
				})
			}
		})
	fetch(`/admin/api/matchups/changed/amount`)
		.then(res => {
			if (res.ok) {
				res.text().then(amount => {
					const card = $('div.suggestions-card');
					const stat = card.find('.card-stats .stat:nth-child(2)');
					stat.find('.stat-value').text(amount);
				})
			}
		})
}

// Tab-Navigation für Suggestions-Popup
$(document).on('click', '.suggestions-tab-btn', function() {
	const tab = $(this).data('tab');
	const popup = $(this).closest('.admin-suggestions-list');

	// Update active tab button
	popup.find('.suggestions-tab-btn').removeClass('active');
	$(this).addClass('active');

	// Update active tab content
	popup.find('.suggestions-tab-content').removeClass('active');
	popup.find(`.suggestions-tab-content[data-tab-content="${tab}"]`).addClass('active');
});

$(document).on('click', 'button.revert-matchup-admin', async function() {
	const matchupId = $(this).data('matchup-id');
	const dialogContent = $(this).closest('.dialog-content');

	const cont = confirm('Matchup wirklich zurücksetzen?');
	if (!cont) return;

	const loadingIndicator = $('<div class="content-loading-indicator"></div>');
	dialogContent.append(loadingIndicator);

	try {
		const response = await fetch(`/admin/api/suggestions/${matchupId}/revert`, {method: 'POST'});

		if (!response.ok) {
			console.error('Error reverting matchup:', response);
			dialogContent.find('.content-loading-indicator').remove();
			return;
		}

		const result = await response.json();

		if (result.error) {
			alert(result.error);
			dialogContent.find('.content-loading-indicator').remove();
			return;
		}

		const content = await adminFragmentLoader(`admin-suggestion-details?matchupId=${matchupId}`);
		dialogContent.html(content);
		updateSuggestionCard();
	} catch (error) {
		console.error('Error reverting matchup:', error);
		alert('Fehler beim zurücksetzen des Spiels');
		dialogContent.find('.content-loading-indicator').remove();
	}
})