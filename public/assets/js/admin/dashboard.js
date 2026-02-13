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
