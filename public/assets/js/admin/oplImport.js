$(document).on("click", "button#turnier-button-get", getTournamentAndShowForm);
$(document).on("click", "button.write_tournament", function () {writeTournamentFromForm(this)});
$(document).on("click", "button.get_related_events", function () {openRelatedEventsPopup(this)});

function getTournamentAndShowForm() {
	const dialog = $('dialog#tournament-add');
	const dialogContent = dialog.find('.dialog-content');
	const tournamentId = $("#input-tournament-id").val();
	if (tournamentId === "") {return}
	add_popupLoadingIndicator(dialog);
	dialogContent.empty();
	dialog[0].showModal();
	fetch(`/admin/api/import/opl/tournaments/get-data?tournamentId=${tournamentId}`)
		.then(res => {
			if (res.ok) {
				return res.json()
			} else {
				dialogContent.empty();
				dialogContent.append("Fehler beim Laden des Turniers");
				return {"error": "Fehler beim Laden des Turniers"};
			}
		})
		.then(tournamentData => {
			if (tournamentData.error) {return}

			fetch(`/admin/ajax/fragment/tournament-edit-form`, {
				method: 'POST',
				headers: {'Content-Type': 'application/json'},
				body: JSON.stringify(tournamentData)
			})
				.then(res => {
					if (res.ok) {
						return res.json()
					} else {
						dialogContent.append("Fehler beim Erstellen des Turnierformulars");
						return "Fehler beim Erstellen des Turnierformulars";
					}
				})
				.then(fragment => {
					dialogContent.append(fragment.html);
					remove_popupLoadingIndicator(dialog);
				})
				.catch(() => {
					dialogContent.append("Fehler beim Erstellen des Turnierformulars");
					remove_popupLoadingIndicator(dialog);
					return "Fehler beim Erstellen des Turnierformulars";
				})
		})
}
function writeTournamentFromForm(button) {
	let tournamentId = $(button).data("id");
	let wrapper = $(button).closest('.tournament-write-data-wrapper');

	let data = {};
	data.OPL_ID = wrapper.find(`input[name=OPL_ID]`).val();
	data.OPL_ID_parent = wrapper.find(`input[name=OPL_ID_parent]`).val();
	data.OPL_ID_top_parent = wrapper.find(`input[name=OPL_ID_top_parent]`).val();
	data.name = wrapper.find(`input[name=name]`).val();
	data.split = wrapper.find(`select[name=split]`).find(":selected").val();
	data.season = wrapper.find(`input[name=season]`).val();
	data.eventType = wrapper.find(`select[name=eventType]`).find(":selected").val();
	data.format = wrapper.find(`select[name=format]`).find(":selected").val();
	data.number = wrapper.find(`input[name=number]`).val();
	data.numberRangeTo = wrapper.find(`input[name=numberRangeTo]`).val();
	data.dateStart = wrapper.find(`input[name=dateStart]`).val();
	data.dateEnd = wrapper.find(`input[name=dateEnd]`).val();
	data.OPL_logo_url = wrapper.find(`input[name=OPL_logo_url]`).val();
	data.OPL_ID_logo = wrapper.find(`input[name=OPL_ID_logo]`).val();
	data.finished = wrapper.find(`input[name=finished]`).prop("checked") ? 1 : 0;
	data.deactivated = wrapper.find(`input[name=deactivated]`).prop("checked") ? 0 : 1;
	data.archived = wrapper.find(`input[name=archived]`).prop("checked") ? 1 : 0;
	data.ranked_season = wrapper.find(`input[name=ranked_season]`).val();
	data.ranked_split = wrapper.find(`input[name=ranked_split]`).val();

	fetch(`/admin/api/import/opl/tournaments/save-from-form`, {
		method: 'POST',
		headers: {'Content-Type': 'application/json'},
		body: JSON.stringify(data)
	})
		.then(res => {
			if (res.ok) {
				return res.json();
			} else {
				return {"error": "Fehler beim Speichern des Turniers"};
			}
		})
		.then(res => {
			if (res.error) {
				show_results_dialog_with_result(tournamentId, res.error);
				return;
			}
			switch (res.result) {
				case "INSERTED":
					show_results_dialog_with_result(tournamentId, "Turnier erfolgreich erstellt");
					break;
				case "UPDATED":
					clear_results_dialog(tournamentId);
					add_to_results_dialog(tournamentId, "Turnier erfolgreich aktualisiert");
					for (let key in res.changes) {
						add_to_results_dialog(tournamentId, `- [${key}] auf '${res.changes[key]}' gesetzt`);
					}
					show_results_dialog(tournamentId);
					break;
				case "NOT_CHANGED":
					show_results_dialog_with_result(tournamentId, "Turnier unverändert");
					break;
				default:
				case "FAILED":
					show_results_dialog_with_result(tournamentId, "Fehler beim Speichern des Turniers");
					break;
			}
		})
		.catch(error => {
			show_results_dialog_with_result(tournamentId, "Fehler beim Speichern des Turniers");
			console.error(error);
		})

}

let related_events_fetch_control = null;
async function openRelatedEventsPopup(button) {
	if (related_events_fetch_control !== null) related_events_fetch_control.abort();
	related_events_fetch_control = new AbortController();

	const tournamentId = button.dataset.id;
	const dialogId = button.dataset.dialogId;
	const relation = button.dataset.relation;
	const dialog = $(`dialog#${dialogId}`);
	if (dialog.length === 0 || tournamentId === '') return;

	add_popupLoadingIndicator(dialog);
	dialog[0].showModal();

	let tournamentIds = [];
	const buttons = dialog.find(`>.dialog-content>.related-event-button-list>button`);
	buttons.each(function () {
		tournamentIds.push(this.dataset.tournamentId)
	})


	let tournamentData = [];

	for (const tournamentId of tournamentIds) {
		await fetch(`/admin/api/import/opl/tournaments/getData?tournamentId=${tournamentId}`, {
			method: 'GET',
			signal: related_events_fetch_control.signal
		})
			.then(res => {
				addToDialogProgressBar(dialog,100/tournamentIds.length)
				if (res.ok) {
					return res.json()
				} else {
					buttons.find(`[data-tournament-id=${tournamentId}]`).html("Fehler beim Laden des Turniers");
					return {"error": "Fehler beim Laden des Turniers"};
				}
			})
			.then(data => {
				if (data.error) return;

				tournamentData.push(data);
			})
	}

	fetch(`/admin/ajax/fragment/related-tournament-list`, {
		method: 'POST',
		headers: {'Content-Type': 'application/json'},
		body: JSON.stringify(tournamentData),
		signal: related_events_fetch_control.signal
	})
		.then(res => {
			if (res.ok) {
				return res.json()
			} else {
				dialog.find('.dialog-content').prepend("Fehler beim Erstellen der Turnierformulare");
				return {"error": "Fehler beim Erstellen der Turnierformulare"};
			}
		})
		.then(fragment => {
			dialog.find('.related-event-button-list').replaceWith(fragment.html);

		})

	console.log(tournamentData);

	remove_popupLoadingIndicator(dialog);
	resetDialogProgressBar(dialog);
}

function clear_results_dialog(tournamentId) {
	$(`dialog#result-popup-${tournamentId}`).empty();
}
function add_to_results_dialog(tournamentId, html) {
	$(`dialog#result-popup-${tournamentId}`).append(html+"<br>");
}
function show_results_dialog(tournamentId) {
	$(`dialog#result-popup-${tournamentId}`)[0].showModal();
}
function show_results_dialog_with_result(tournamentId, html) {
	clear_results_dialog(tournamentId);
	add_to_results_dialog(tournamentId, html);
	show_results_dialog(tournamentId);
}