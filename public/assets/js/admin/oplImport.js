import {setButtonUpdating, unsetButtonUpdating, setButtonLoadingBarWidth, finishButtonUpdating} from "../utils/updatingButton";

$(document).on("click", "button#turnier-button-get", getTournamentAndShowForm);
$(document).on("click", "button.write_tournament", function () {writeTournamentFromForm(this)});
$(document).on("click", "button.update_tournament", function () {writeTournamentFromForm(this)});
$(document).on("click", "button.get_related_events", function () {openRelatedEventsPopup(this)});

// Beim Start nach laufenden Jobs suchen und Buttons aktualisieren
$(async function () {
	// rufe alle Jobs ab
	const runningJobs = await fetch(`/api/jobs/admin/running`)
		.then(res => {
			if (res.ok) {
				return res.json()
			} else {
				console.error("Fehler beim Laden der laufenden Jobs");
			}
		})
		.catch(error => console.error(error));
	if (!runningJobs || runningJobs.error) return;

	for (const job of runningJobs) {
		// Für OPL-Updates sind nur Jobs mit korrektem Turnier-Kontext relevant
		if (job['contextType'] !== 'tournament') continue;
		if (!job['context'] || !job['context']['id']) continue;

		// Suche passende Buttons
		const tournamentId = job['context']['id'];
		const action = job['action'];
		const button = $(`button[data-id=${tournamentId}][data-action=${action}]`);
		if (button.length === 0) continue;
		setButtonUpdating(button);
		checkJobStatusRepeatedly(job['id'], 1000, button)
			.then(() => {
				finishButtonUpdating(button);
			})
			.catch(error => console.error(error))
	}
})
function getTournamentAndShowForm() {
	const dialog = $('dialog#tournament-add');
	const dialogContent = dialog.find('.dialog-content');
	const tournamentId = $("#input-tournament-id").val();
	if (tournamentId === "") {return}
	add_popupLoadingIndicator(dialog);
	dialogContent.empty();
	dialog[0].showModal();
	fetch(`/admin/api/opl/tournaments/${tournamentId}`)
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
					fragment.css?.forEach(href => {
						if (!document.querySelector(`link[href="${href}"]`)) {
							const link = document.createElement('link');
							link.rel = 'stylesheet';
							link.href = href;
							document.head.appendChild(link);
						}
					})
					fragment.js?.forEach(src => {
						if (!document.querySelector(`script[src="${src}"]`)) {
							const script = document.createElement('script');
							script.src = src;
							script.defer = true;
							document.body.appendChild(script);
						}
					})
					dialogContent.append(fragment.html);
					remove_popupLoadingIndicator(dialog);
				})
				.catch(error => {
					console.warn(error);
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
	data.ranked_splits = [];

	const selectedSplitElements = wrapper.find(`label.write_tournament_ranked_splits .multi-select-options label>input:checked`);
	selectedSplitElements.each(function () {
		data.ranked_splits.push(this.value);
	});

	fetch(`/admin/api/opl/tournaments`, {
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
				case "inserted":
					show_results_dialog_with_result(tournamentId, "Turnier erfolgreich erstellt");
					break;
				case "updated":
					clear_results_dialog(tournamentId);
					add_to_results_dialog(tournamentId, "Turnier erfolgreich aktualisiert");
					for (let key in res.changes) {
						if (typeof res.changes[key] == "object") {
							add_to_results_dialog(tournamentId, `- [${key}] angepasst`)
						} else {
							add_to_results_dialog(tournamentId, `- [${key}] auf '${res.changes[key]}' gesetzt`);
						}
					}
					show_results_dialog(tournamentId);
					break;
				case "not-changed":
					show_results_dialog_with_result(tournamentId, "Turnier unverändert");
					break;
				default:
				case "failed":
					show_results_dialog_with_result(tournamentId, "Fehler beim Speichern des Turniers");
					break;
			}
		})
		.catch(error => {
			show_results_dialog_with_result(tournamentId, "Fehler beim Speichern des Turniers");
			console.error(error);
		})

}

$(document).on("click", "button.get-tournament-logo", async function () {
	const tournamentId = this.dataset.id;
	setButtonUpdating($(this));

	const logoResult = await downloadTournamentLogos(tournamentId);

	let resultText = "Logo herunterladen:<br>";
	if (logoResult.error) {
		resultText += "Fehler beim Herunterladen: "+logoResult.error;
	} else {
		resultText += "Logo heruntergeladen: "+logoResult.LogoReceived +"<br>";
		resultText += "Logo gespeichert: "+logoResult.LogoUpdated;
	}

	show_results_dialog_with_result(tournamentId, resultText);
	finishButtonUpdating($(this));
})
async function downloadTournamentLogos(tournamentId) {
	return await fetch(`/admin/api/opl/tournaments/${tournamentId}/logos`, {method: 'POST'})
		.then(res => {
			if (res.ok) {
				return res.json();
			} else {
				return {"error": "Fehler beim Laden des Logos"};
			}
		})
		.then(data => {
			return data;
		})
}

$(document).on("click", "button.refresh-button.refresh-tournaments", function () {refresh_tournament_edit_list(this)});
function refresh_tournament_edit_list(button) {
	button.innerHTML = "Refreshing...";

	const open_accordeons = sessionStorage.getItem("open_admin_accordeons") ?? "[]";
	fetch(`/admin/ajax/fragment/tournament-edit-list`, {
		method: "POST",
		headers: {'Content-Type': 'application/json'},
		body: open_accordeons
	})
		.then(res => {
			if (res.ok) {
				return res.json();
			} else {
				return {"error": "Fehler beim Laden der Turnierliste"};
			}
		})
		.then(fragment => {
			if (fragment.error) {
				button.innerHTML = "Refresh";
				return;
			}
			document.getElementsByClassName("turnier-select")[0].innerHTML = fragment.html;
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

	if (tournamentIds.length === 0) {
		await fetch(`/admin/api/opl/tournaments/${tournamentId}`)
			.then(res => {
				if (res.ok) {
					return res.json()
				} else {
					dialog.find('.related-event-button-list').html("Fehler beim Laden der Turniere");
					return {"error": "Fehler beim Laden der Turniere"};
				}
			})
			.then(data => {
				if (data.error) return;

				tournamentIds = data['relatedTournaments'][relation];
			})
	}
	if (tournamentIds.length === 0) {
		remove_popupLoadingIndicator(dialog);
		resetDialogProgressBar(dialog);
		return;
	}

	let tournamentData = [];

	for (const tournamentId of tournamentIds) {
		await fetch(`/admin/api/opl/tournaments/${tournamentId}`, {
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
				dialog.find('.related-event-button-list').html("Fehler beim Erstellen der Turnierformulare");
				return {"error": "Fehler beim Erstellen der Turnierformulare"};
			}
		})
		.then(fragment => {
			if (fragment.error) return;

			dialog.find('.related-event-button-list').replaceWith(fragment.html);
		})

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

function renderDetails(summary,text) {
	return `<details><summary>${summary}</summary><span>${text}</span></details>`;
}

$(document).on("click", ".toggle-turnierselect-accordeon", function () {
	toggle_turnier_select_accordeon(this.getAttribute("data-id"));
});
$(() => {
	const open_accordeons = sessionStorage.getItem("open_admin_accordeons") ?? "[]";
	const open_accordeon_ids = JSON.parse(open_accordeons);
	open_accordeon_ids.forEach(id => {
		$(`.toggle-turnierselect-accordeon[data-id=${id}]`).addClass("open");
		$(".turnierselect-accordeon."+id).addClass("open");
	})
})
function toggle_turnier_select_accordeon(tournamentID) {
	$(".turnierselect-accordeon."+tournamentID).toggleClass("open");
	$(`.toggle-turnierselect-accordeon[data-id=${tournamentID}]`).toggleClass("open");

	const open_accordeons = $(`.toggle-turnierselect-accordeon.open`);
	let open_accordeon_ids = [];
	open_accordeons.each(function() {open_accordeon_ids.push(this.getAttribute("data-id"))})
	sessionStorage.setItem("open_admin_accordeons",JSON.stringify(open_accordeon_ids));
}


// Teams aktualisieren
$(document).on("click", "button.get-teams", async function () {
	const tournamentId = this.dataset.id;
	await handleUpdateButton(this,`/admin/api/opl/tournaments/${tournamentId}/teams`);
})

// Spieler aktualisieren
$(document).on("click", "button.get-players", async function () {
	const tournamentId = this.dataset.id;
	await handleUpdateButton(this,`/admin/api/opl/tournaments/${tournamentId}/players`);
})

// RiotIds aktualisieren
$(document).on("click", "button.get-riotids", async function () {
	const tournamentId = this.dataset.id;
	await handleUpdateButton(this,`/admin/api/opl/tournaments/${tournamentId}/players/accounts`);
})

// Matchups aktualisieren
$(document).on("click", "button.get-matchups", async function () {
	const tournamentId = this.dataset.id;
	await handleUpdateButton(this,`/admin/api/opl/tournaments/${tournamentId}/matchups`);
})

// Matchresults aktualisieren und Spiel-Ids holen
$(document).on("click", "button.get-results", async function () {
	const tournamentId = this.dataset.id;
	await handleUpdateButton(this,`/admin/api/opl/tournaments/${tournamentId}/matchups/results`);
})
$(document).on("click", "button.get-results-unplayed", async function () {
	const tournamentId = this.dataset.id;
	await handleUpdateButton(this,`/admin/api/opl/tournaments/${tournamentId}/matchups/results?unplayed=true`);
})

// Standings aktualisieren
$(document).on("click", "button.calculate-standings", async function () {
	const tournamentId = this.dataset.id;
	await handleUpdateButton(this,`/admin/api/opl/tournaments/${tournamentId}/standings`);
})


/* Background-Job Helfer */
async function startJob(endpoint) {
	return await fetch(endpoint, {method: 'POST'})
		.then(res => {
			if (res.ok) {
				return res.json()
			} else {
				return {"error": "Fehler beim Starten des Jobs"};
			}
		})
		.catch(e => console.error(e));
}
async function checkJobStatusRepeatedly(jobId, interval, button = null) {
	let job = null;
	while (true) {
		job = await checkJobStatus(jobId);
		if (job.error) {
			if (button !== null) unsetButtonUpdating(button, true);
			console.error(job.error);
			return null;
		}
		if (job.status !== "running" && job.status !== "queued") {
			if (button !== null) unsetButtonUpdating(button);
			break;
		}
		if (button !== null) setButtonLoadingBarWidth(button, Math.round(job.progress));
		await new Promise(r => setTimeout(r, interval));
	}
	return job;
}
async function checkJobStatus(jobId) {
	return await fetch(`/api/jobs/${jobId}`)
		.then(res => {
			if (res.ok) {
				return res.json()
			} else {
				return {"error": "Fehler beim Laden der Daten"};
			}
		})
		.catch(e => {
			console.error(e);
			return {"error": "Fehler beim Laden der Daten"};
		});
}

async function handleUpdateButton(button, endpoint) {
	const tournamentId = button.dataset.id;
	const jqButton = $(button);
	const jobResponse = await startJob(endpoint);
	if (jobResponse.error) {
		console.error(jobResponse.error);
		return;
	}
	const jobId = parseInt(jobResponse["job_id"]);
	setButtonUpdating(jqButton);
	const job = await checkJobStatusRepeatedly(jobId, 1000, jqButton);
	if (job !== null) {
		show_results_dialog_with_result(
			tournamentId,
			job?.resultMessage + renderDetails("Details", job?.message)
		)
	}
	finishButtonUpdating(jqButton);
}