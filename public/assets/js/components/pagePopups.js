$(document).ready(() => {
	$("dialog.modalopen_auto").get().forEach(element => element.showModal());
	$("dialog.match-popup.modalopen_auto").get().forEach(element => current_matchpopups_loaded.push(element.id));
});

$(document).on("click", "button.player-ov-card", function () {open_popup_player(this, true)});
$(document).on("click", ".summoner-card-wrapper button.open-playerhistory", function () {open_popup_player(this)});
let current_player_popups_open = [];
async function open_popup_player(button,add_to_recents=false) {
	let dialogId = button.dataset.dialogId;
	let playerId = button.dataset.playerId;

	if (add_to_recents) {
		addPlayerToRecents(playerId, ($(button).closest(".recent-players-list").length === 0));
	}

	let dialog = $(`dialog#${dialogId}`);
	if (dialog.length === 0) {
		return;
	}

	if (current_player_popups_open.includes(dialogId)) {
		dialog[0].showModal();
		return;
	}
	current_player_popups_open.push(dialogId);

	let dialogContent = dialog.find('.dialog-content');
	dialogContent.empty();

	add_popupLoadingIndicator(dialog);

	fragmentLoader(`player-overview?playerId=${playerId}`, null, () => {
		current_player_popups_open = current_player_popups_open.filter(id => id !== dialogId);
	})
		.then(content => {
			dialogContent.append(content);
			remove_popupLoadingIndicator(dialog);
		})

	dialog[0].showModal();
}
async function remove_popupLoadingIndicator(popup) {
	let popup_loader = popup.find('.popup-loading-indicator');
	popup_loader.css("opacity","0");
	await new Promise(r => setTimeout(r, 210));
	popup_loader.remove();
}
function add_popupLoadingIndicator(popup) {
	popup.prepend("<div class='popup-loading-indicator'></div>");
}
function addPlayerToRecents(playerId, reload_recents=true) {
	let recents = JSON.parse(localStorage.getItem("searched_players_IDs"));
	if (recents === null) {
		recents = [playerId];
	}
	if (recents.includes(playerId)) {
		let index = recents.indexOf(playerId);
		recents.splice(index,1);
	}
	recents.unshift(playerId);
	while (recents.length > 5) {
		recents = recents.slice(0,5);
	}
	localStorage.setItem("searched_players_IDs",JSON.stringify(recents));
	if (reload_recents && $("body.players").length) {
		reload_recent_players();
	}
}

$(document).on("click", "button.team-button", function () {open_popup_team(this)});
$(document).on("click", "button.elo-list-item.team", function () {open_popup_team(this)});
let current_team_popups_open = [];
async function open_popup_team(button) {
	let dialogId = button.dataset.dialogId;
	let teamId = button.dataset.teamId;
	let tournamentId = button.dataset.tournamentId;

	let dialog = $(`dialog#${dialogId}`);
	if (dialog.length === 0) {
		return;
	}

	if (current_team_popups_open.includes(dialogId)) {
		dialog[0].showModal();
		return;
	}
	current_team_popups_open.push(dialogId);

	let dialogContent = dialog.find('.dialog-content');
	dialogContent.empty();

	add_popupLoadingIndicator(dialog);

	fragmentLoader(`team-popup?teamId=${teamId}&tournamentId=${tournamentId}`, null, () => {
		current_team_popups_open = current_team_popups_open.filter(id => id !== dialogId);
	})
		.then(content => {
			dialogContent.append(content);
			remove_popupLoadingIndicator(dialog);
		})

	dialog[0].showModal();
}

$(document).on("click", "a.button.match", function () {open_popup_match(this, event)});
let current_matchpopups_loaded = []
async function open_popup_match(button, event) {
	event.preventDefault();
	let dialogId = button.dataset.dialogId;
	let matchId = button.dataset.matchId;
	let teamId = button.dataset.teamId;

	let dialog = $(`dialog#${dialogId}`);
	if (dialog.length === 0) {
		return;
	}

	if (current_matchpopups_loaded.includes(dialogId)) {
		dialog[0].showModal();
		setParamAndUpdateUrl("match",matchId);
		return;
	}
	current_matchpopups_loaded.push(dialogId);

	let dialogContent = dialog.find('.dialog-content');
	dialogContent.empty();

	add_popupLoadingIndicator(dialog);

	fragmentLoader(`match-popup?matchId=${matchId}&teamId=${teamId}`, null, () => {
		current_matchpopups_loaded = current_matchpopups_loaded.filter(id => id !== dialogId);
	})
		.then(content => {
			dialogContent.append(content);
			remove_popupLoadingIndicator(dialog);
		})

	dialog[0].showModal();
	setParamAndUpdateUrl("match",matchId);
}

function setParamAndUpdateUrl(param, value) {
	let url = new URL(window.location.href);
	url.searchParams.set(param,value);
	window.history.replaceState({}, '', url);
}
function bindDialogCloseHandler(dialog) {
	if (!dialog.hasAttribute('data-close-bound')) {
		dialog.addEventListener('close', () => {
			let url = new URL(window.location.href);
			url.searchParams.delete('match');
			window.history.replaceState({}, '', url);
		});
		dialog.setAttribute('data-close-bound', 'true');
	}
}

const closeMatchObserver = new MutationObserver(mutations => {
	for (const mutation of mutations) {
		$(mutation.addedNodes).each(function () {
			$(this).find('dialog.match-popup:not([data-close-bound])').each(function () {
				bindDialogCloseHandler(this);
			})

		});
	}
});
$(document).ready(() => {
	$('dialog.match-popup').each(function () {bindDialogCloseHandler(this)});
	closeMatchObserver.observe(document.body, {childList: true, subtree: true});
})