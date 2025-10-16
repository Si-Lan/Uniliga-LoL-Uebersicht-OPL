$(document).on("click", "button#turnier-button-get", getTournamentAndShowForm);
$(document).on("click", "button.write_tournament", function () {writeTournamentFromForm(this)});
$(document).on("click", "button.update_tournament", function () {writeTournamentFromForm(this)});
$(document).on("click", "button.get_related_events", function () {openRelatedEventsPopup(this)});

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
						add_to_results_dialog(tournamentId, `- [${key}] auf '${res.changes[key]}' gesetzt`);
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
	unsetButtonUpdating($(this));
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

$(document).on("click", ".toggle-turnierselect-accordeon", function () {
	toggle_turnier_select_accordeon(this.getAttribute("data-id"));
});
function toggle_turnier_select_accordeon(tournamentID) {
	$(".turnierselect-accordeon."+tournamentID).toggleClass("open");
	$(`.toggle-turnierselect-accordeon[data-id=${tournamentID}]`).toggleClass("open");

	const open_accordeons = $(`.toggle-turnierselect-accordeon.open`);
	let open_accordeon_ids = [];
	open_accordeons.each(function() {open_accordeon_ids.push(this.getAttribute("data-id"))})
	sessionStorage.setItem("open_admin_accordeons",JSON.stringify(open_accordeon_ids));
}

function setButtonUpdating(button) {
	setButtonLoadingBarWidth(button, 0);
	button.addClass("button-updating");
	button.prop("disabled",true);
}
function unsetButtonUpdating(button) {
	button.removeClass("button-updating");
	button.prop("disabled",false);
	setButtonLoadingBarWidth(button, 0);
}
function setButtonLoadingBarWidth(button, widthPercentage) {
	button.attr("style", `--loading-bar-width: ${widthPercentage}%`);
}

// Teams aktualisieren
$(document).on("click", "button.get-teams", async function () {
	const tournamentId = this.dataset.id;
	setButtonUpdating($(this));
	const childrenTournaments = await getStandingEventsInTournament(tournamentId);
	if (childrenTournaments.error) {
		show_results_dialog_with_result(tournamentId, "Fehler beim Prüfen des Turniers");
		unsetButtonUpdating($(this));
		return;
	}
	let result = "";
	if (childrenTournaments.length === 0) {
		result = await updateTeamsInTournament(tournamentId);
	} else {
		const total = childrenTournaments.length;
		let donePercentage = 0;
		for (const childTournament of childrenTournaments) {
			const partialResult = await updateTeamsInTournament(childTournament.id);
			result += `<br>(${childTournament.id}) ${childTournament.name}:<br> ${partialResult}<br>`;
			donePercentage += 100/total;
			setButtonLoadingBarWidth($(this), donePercentage);
			if (donePercentage < 100) {
				await new Promise(r => setTimeout(r, 1000));
			}
		}
	}
	show_results_dialog_with_result(tournamentId, result);
	unsetButtonUpdating($(this));
});
async function updateTeamsInTournament(tournamentId) {
	return await fetch(`/admin/api/opl/tournaments/${tournamentId}/teams`, {method: 'POST'})
		.then(res => {
			if (res.ok) {
				return res.json()
			} else {
				return {"error": "Fehler beim Aktualisieren der Teams"};
			}
		})
		.then(data => {
			if (data.error) {
				return data.error;
			}
			let teamCount = data.teams.length;
			let unchangedTeams = [];
			let addedTeams = [];
			let updatedTeams = [];
			let removedTeams = [];
			for (const team of data.teams) {
				switch (team.result) {
					case "inserted":
						addedTeams.push(team.team.name);
						break;
					case "updated":
						updatedTeams.push(team.team.name);
						break;
					case "not-changed":
						unchangedTeams.push(team.team.name);
						break;
				}
			}
			for (const removedTeam of data.removedTeams) {
				removedTeams.push(removedTeam.name);
			}
			let addedTeamsToTournament = [];
			for (const addedTeam of data.addedTeams) {
				addedTeamsToTournament.push(addedTeam.name);
			}
			return `${teamCount} Teams im Event<br>
						- ${unchangedTeams.length} allgemein unverändert<br>
						- ${addedTeams.length} allgemein neu<br>
						- ${updatedTeams.length} allgemein aktualisiert<br>
						${addedTeamsToTournament.length} Teams zu Turnier hinzugefügt<br>
						${removedTeams.length} Teams aus Turnier entfernt`;
		})
		.catch(error => {
			console.error(error);
			return "Fehler beim Aktualisieren der Teams";
		})
}

// Spieler aktualisieren
$(document).on("click", "button.get-players", async function () {
	const tournamentId = this.dataset.id;
	setButtonUpdating($(this));
	const teamsInTournaments = await getTeamsInTournament(tournamentId);
	if (teamsInTournaments.error) {
		show_results_dialog_with_result(tournamentId, "Fehler beim Holen der Teams");
		unsetButtonUpdating($(this));
		return;
	}
	if (teamsInTournaments.length === 0) {
		show_results_dialog_with_result(tournamentId, "Keine Teams im Turnier");
		unsetButtonUpdating($(this));
		return;
	}
	let result = "";
	const total = teamsInTournaments.length;
	let donePercentage = 0;
	for (const teamInTournament of teamsInTournaments) {
		const partialResult = await updatePlayersInTeam(teamInTournament.team.id);
		result += `<br>(${teamInTournament.team.id}) ${teamInTournament.team.name}:<br> ${partialResult}<br>`;
		donePercentage += 100/total;
		setButtonLoadingBarWidth($(this), donePercentage);
		if (donePercentage < 100) {
			await new Promise(r => setTimeout(r, 1000));
		}
	}
	show_results_dialog_with_result(tournamentId, result);
	unsetButtonUpdating($(this));
})
async function updatePlayersInTeam(teamId) {
	return await fetch(`/admin/api/opl/teams/${teamId}/players`, {method: 'POST'})
		.then(res => {
			if (res.ok) {
				return res.json()
			} else {
				return {"error": "Fehler beim Aktualisieren der Spieler"};
			}
		})
		.then(data => {
			if (data.error) {
				return data.error;
			}
			let playerCount = data.players.length;
			let unchangedPlayers = [];
			let addedPlayers = [];
			let updatedPlayers = [];
			for (const player of data.players) {
				switch (player.result) {
					case "inserted":
						addedPlayers.push(player.player?.name);
						break;
					case "updated":
						updatedPlayers.push(player.player?.name);
						break;
					case "not-changed":
						unchangedPlayers.push(player.player?.name);
						break;
				}
			}
			let removedPlayersFromTeam = [];
			for (const removedPlayer of data.removedPlayers) {
				removedPlayersFromTeam.push(removedPlayer?.name);
			}
			let addedPlayersToTeam = [];
			for (const addedPlayer of data.addedPlayers) {
				addedPlayersToTeam.push(addedPlayer?.name);
			}
			
			let tournamentResults = "";
			for (const tournamentChange of data.tournamentChanges) {
				let removedPlayersFromTeamInTournament = [];
				for (const removedPlayer of tournamentChange.removedPlayers) {
					removedPlayersFromTeamInTournament.push(removedPlayer?.name);
				}
				let addedPlayersToTeamInTournament = [];
				for (const addedPlayer of tournamentChange.addedPlayers) {
					addedPlayersToTeamInTournament.push(addedPlayer?.name);
				}
				tournamentResults += `<br>In Turnier (${tournamentChange.tournament.id}) ${tournamentChange.tournament.name}:<br>
										- ${addedPlayersToTeamInTournament.length} Spieler zum Team hinzugefügt<br>
										- ${removedPlayersFromTeamInTournament.length} Spieler aus Team entfernt`;
			}

			return `${playerCount} Spieler im Team<br>
						- ${unchangedPlayers.length} allgemein unverändert<br>
						- ${addedPlayers.length} allgemein neu<br>
						- ${updatedPlayers.length} allgemein aktualisiert<br>
						${addedPlayersToTeam.length} Spieler zum Team hinzugefügt<br>
						${removedPlayersFromTeam.length} Spieler aus Team entfernt
						${tournamentResults}`;
		})
		.catch(error => {
			console.error(error);
			return "Fehler beim Aktualisieren der Spieler";
		})
}

// RiotIds aktualisieren
$(document).on('click', 'button.get-riotids', async function () {
	const tournamentId = this.dataset.id;
	setButtonUpdating($(this));
	const teamsInTournaments = await getTeamsInTournament(tournamentId);
	if (teamsInTournaments.error) {
		show_results_dialog_with_result(tournamentId, "Fehler beim Holen der Teams");
		unsetButtonUpdating($(this));
		return;
	}
	if (teamsInTournaments.length === 0) {
		show_results_dialog_with_result(tournamentId, "Keine Teams im Turnier");
		unsetButtonUpdating($(this));
	}
	let result = "";
	const totalTeams = teamsInTournaments.length;
	let donePercentage = 0;
	for (const teamInTournament of teamsInTournaments) {
		const partialResult = await updatePlayerAccountsInTeam(teamInTournament.team.id);
		result += `<br>(${teamInTournament.team.id}) ${teamInTournament.team.name}:<br> ${partialResult}<br>`;
		donePercentage += 100/totalTeams;
		setButtonLoadingBarWidth($(this), donePercentage);
		if (donePercentage < 100) {
			await new Promise(r => setTimeout(r, 1000));
		}
	}
	show_results_dialog_with_result(tournamentId, result);
	unsetButtonUpdating($(this));
})
async function updatePlayerAccountsInTeam(teamId) {
	return await fetch(`/admin/api/opl/teams/${teamId}/players/accounts`, {method: 'POST'})
		.then(res => {
			if (res.ok) {
				return res.json()
			} else {
				return {"error": "Fehler beim Aktualisieren der RiotIds"};
			}
		})
		.then(data => {
			if (data.error) {
				return data.error;
			}
			let playerCount = data.players.length + data.errors.length;
			let playersUpdated = [];
			let playersUnchanged = [];
			for (const player of data.players) {
				switch (player.result) {
					case "updated":
						playersUpdated.push(player.player?.name);
						break;
					case "not-changed":
						playersUnchanged.push(player.player?.name);
						break;
				}
			}
			return `${playerCount} Spieler im Team<br>
						- ${playersUpdated.length} RiotIds aktualisiert<br>
						- ${playersUnchanged.length} Spieler unverändert<br>
						- ${data.errors.length} Fehler beim Aktualisieren`;
		})
}

// Matchups aktualisieren
$(document).on('click', 'button.get-matchups', async function () {
	const tournamentId = this.dataset.id;
	setButtonUpdating($(this));
	const childrenTournaments = await getStandingEventsInTournament(tournamentId);
	if (childrenTournaments.error) {
		show_results_dialog_with_result(tournamentId, "Fehler beim Prüfen des Turniers");
		unsetButtonUpdating($(this));
		return;
	}
	let result = "";
	if (childrenTournaments.length === 0) {
		result = await updateMatchupsInTournament(tournamentId);
	} else {
		const total = childrenTournaments.length;
		let donePercentage = 0;
		for (const childTournament of childrenTournaments) {
			const partialResult = await updateMatchupsInTournament(childTournament.id);
			result += `<br>(${childTournament.id}) ${childTournament.name}:<br> ${partialResult}<br>`;
			donePercentage += 100/total;
			setButtonLoadingBarWidth($(this), donePercentage);
			if (donePercentage < 100) {
				await new Promise(r => setTimeout(r, 1000));
			}
		}
	}
	show_results_dialog_with_result(tournamentId, result);
	unsetButtonUpdating($(this));
});
async function updateMatchupsInTournament(tournamentId) {
	return await fetch(`/admin/api/opl/tournaments/${tournamentId}/matchups`, {method: 'POST'})
		.then(res => {
			if (res.ok) {
				return res.json()
			} else {
				return {"error": "Fehler beim Aktualisieren der Matchups"};
			}
		})
		.then(data => {
			if (data.error) {
				return data.error;
			}
			let matchupCount = data.matchups.length;
			let unchangedMatchups = [];
			let addedMatchups = [];
			let updatedMatchups = [];
			let removedMatchups = [];
			for (const matchup of data.matchups) {
				switch (matchup.result) {
					case "inserted":
						addedMatchups.push(matchup.matchup?.id);
						break;
					case "updated":
						updatedMatchups.push(matchup.matchup?.id);
						break;
					case "not-changed":
						unchangedMatchups.push(matchup.matchup?.id);
						break;
				}
			}
			for (const removedMatchup of data.removedMatchups) {
				removedMatchups.push(removedMatchup?.id);
			}
			return `${matchupCount} Matchups im Event<br>
					- ${unchangedMatchups.length} unverändert<br>
					- ${addedMatchups.length} neu<br>
					- ${updatedMatchups.length} aktualisiert<br>
					- ${removedMatchups.length} entfernt`;
		})
		.catch(error => {
			console.error(error);
			return "Fehler beim Aktualisieren der Matchups";
		})
}

// Matchresults aktualisieren und Spiel-Ids holen
$(document).on('click', 'button.get-results', async function () {
	await buttonGetResultsClick(this);
});
$(document).on('click', 'button.get-results-unplayed', async function () {
	await buttonGetResultsClick(this, true);
});
async function buttonGetResultsClick(button, unplayedOnly = false) {
	const tournamentId = button.dataset.id;
	setButtonUpdating($(button));
	const matchupsInTournament = await getMatchupsInTournament(tournamentId, unplayedOnly);
	if (matchupsInTournament.error) {
		show_results_dialog_with_result(tournamentId, "Fehler beim Holen der Matchups");
		unsetButtonUpdating($(button));
		return;
	}
	if (matchupsInTournament.length === 0) {
		show_results_dialog_with_result(tournamentId, "Keine Matchups im Turnier");
		unsetButtonUpdating($(button));
		return;
	}
	let result = "";
	const total = matchupsInTournament.length;
	let donePercentage = 0;
	for (const matchup of matchupsInTournament) {
		const partialResult = await updateMatchresult(matchup.id);
		result += `<br>(${matchup.id}):<br> ${partialResult}<br>`;
		donePercentage += 100/total;
		setButtonLoadingBarWidth($(button), donePercentage);
		if (donePercentage < 100) {
			await new Promise(r => setTimeout(r, 1000));
		}
	}
	show_results_dialog_with_result(tournamentId, result);
	unsetButtonUpdating($(button));
}
async function updateMatchresult(matchId) {
	return await fetch(`/admin/api/opl/matchups/${matchId}/results`, {method: 'POST'})
		.then(res => {
			if (res.ok) {
				return res.json()
			} else {
				return {"error": "Fehler beim Aktualisieren der Matchresults"};
			}
		})
		.then(data => {
			if (data.error) {
				return data.error;
			}
			let scoreUpdated = data.matchup?.changes && ("team1Score" in data.matchup.changes || "team2Score" in data.matchup.changes);
			let addedGames = [];
			let updatedGames = [];
			let unchangedGames = [];
			for (const game of data.games) {
				switch (game.result) {
					case "inserted":
						addedGames.push(game.game?.id);
						break;
					case "updated":
						updatedGames.push(game.game?.id);
						break;
					case "not-changed":
						unchangedGames.push(game.game?.id);
						break;
				}
			}
			let addedGamesInMatchup = [];
			let updatedGamesInMatchup = [];
			let unchangedGamesInMatchup = [];
			for (const gameInMatchup of data.gamesInMatchup) {
				switch (gameInMatchup.result) {
					case "inserted":
						addedGamesInMatchup.push(gameInMatchup.gameInMatch?.id);
						break;
					case "updated":
						updatedGamesInMatchup.push(gameInMatchup.gameInMatch?.id);
						break;
					case "not-changed":
						unchangedGamesInMatchup.push(gameInMatchup.gameInMatch?.id);
						break;
				}
			}

			let result = `Matchup ${data.matchup.result}<br>
					${scoreUpdated ? "Score aktualisiert<br>" : ""}`;
			if (data.games.length > 0) {
				result += `
					- ${unchangedGames.length} Spiele unverändert<br>
					- ${addedGames.length} Spiele hinzugefügt<br>
					- ${updatedGames.length} Spiele aktualisiert<br>`;
			}
			if (data.gamesInMatchup.length > 0) {
				result += `
					-- ${unchangedGamesInMatchup.length} Spiele für Match unverändert<br>
					-- ${addedGamesInMatchup.length} Spiele für Match eingetragen<br>
					-- ${updatedGamesInMatchup.length} Spiele für Match aktualisiert<br>`;
			}
			return result;
		})
		.catch(error => {
			console.error(error);
			return "Fehler beim Darstellen der aktualisierten Matchresults";
		})
}

$(document).on('click', 'button.calculate-standings', async function () {
	const tournamentId = this.dataset.id;
	setButtonUpdating($(this));
	const childrenTournaments = await getStandingEventsInTournament(tournamentId);
	if (childrenTournaments.error) {
		show_results_dialog_with_result(tournamentId, "Fehler beim Prüfen des Turniers");
		unsetButtonUpdating($(this));
		return;
	}
	let result = "";
	if (childrenTournaments.length === 0) {
		result = await updateStandingsInTournament(tournamentId);
	} else {
		const total = childrenTournaments.length;
		let donePercentage = 0;
		for (const childTournament of childrenTournaments) {
			const partialResult = await updateStandingsInTournament(childTournament.id);
			result += `<br>(${childTournament.id}) ${childTournament.name}:<br> ${partialResult}<br>`;
			donePercentage += 100/total;
			setButtonLoadingBarWidth($(this), donePercentage);
			if (donePercentage < 100) {
				await new Promise(r => setTimeout(r, 1000));
			}
		}
	}
	show_results_dialog_with_result(tournamentId, result);
	unsetButtonUpdating($(this));
})
async function updateStandingsInTournament(tournamentId) {
	return await fetch(`/admin/api/opl/tournaments/${tournamentId}/standings`, {method: 'POST'})
		.then(res => {
			if (res.ok) {
				return res.json()
			} else {
				return {"error": "Fehler beim Aktualisieren der Standings"};
			}
		})
		.then(data => {
			if (data.error) {
				return data.error;
			}
			let unchangedTeams = [];
			let updatedTeams = [];
			for (const team of data) {
				switch (team.result) {
					case "updated":
						updatedTeams.push(team.teamInTournamentStage?.team.name);
						break;
					case "not-changed":
						unchangedTeams.push(team.teamInTournamentStage?.team.name);
						break;
				}
			}
			return `${data.length} Teams im Turnier<br>
					- Standings für ${unchangedTeams.length} Teams unverändert<br>
					- Standings für ${updatedTeams.length} Teams aktualisiert`;
		})
		.catch(error => {
			console.error(error);
			return "Fehler beim Aktualisieren der Standings";
		})
}


// Allgemeine API-Abfragen
/**
 * interne API Anfrage liefert Liste an untergeordneten Stage-Events unter einem Event
 */
async function getStandingEventsInTournament(tournamentId) {
	return await fetch(`/api/tournaments/${tournamentId}/leafes`, {method: 'GET'})
		.then(res => {
			if (res.ok) {
				return res.json()
			} else {
				return {"error": "Fehler"};
			}
		})
		.then(data => {
			if (data.error) {
				return {"error": data.error};
			}
			return data;
		});
}

/**
 * interne API-Anfrage liefert alle Teams in untergeordneten Stage-Events unter einem Event
 */
async function getTeamsInTournament(tournamentId) {
	const event = await fetch(`/api/tournaments/${tournamentId}`, {method: 'GET'})
		.then(res => {
			if (res.ok) {
				return res.json()
			} else {
				return {"error": "Fehler beim Abrufen des Turniers"};
			}
		})
		.then(data => {
			if (data.error) {
				return {"error": data.error};
			}
			return data;
		})

	let queryParameter = "";
	if (event.eventType !== "tournament") {
		tournamentId = event.rootTournament.id;
		queryParameter = `?filterBySubEvent=${event.id}`
	}

	return await fetch(`/api/tournaments/${tournamentId}/teams${queryParameter}`, {method: 'GET'})
		.then(res => {
			if (res.ok) {
				return res.json()
			} else {
				return {"error": "Fehler beim Abrufen der Teams"};
			}
		})
		.then(data => {
			if (data.error) {
				return {"error": data.error};
			}
			return data;
		})
}

/**
 * interne API-Anfrage liefert alle Matchups in untergeordneten Stage-Events unter einem Event
 */
async function getMatchupsInTournament(tournamentId, onlyUnplayed = false) {
	const event = await fetch(`/api/tournaments/${tournamentId}`, {method: 'GET'})
		.then(res => {
			if (res.ok) {
				return res.json()
			} else {
				return {"error": "Fehler beim Abrufen des Turniers"};
			}
		})
		.then(data => {
			if (data.error) {
				return {"error": data.error};
			}
			return data;
		})

	let queryParameter = "";
	if (event.eventType !== "tournament") {
		tournamentId = event.rootTournament.id
		queryParameter = `?filterBySubEvent=${event.id}`
	}
	if (onlyUnplayed) {
		queryParameter += (queryParameter.length > 0 ? "&" : "?")
		queryParameter += "unplayed=true";
	}

	return await fetch(`/api/tournaments/${tournamentId}/matchups${queryParameter}`, {method: 'GET'})
		.then(res => {
			if (res.ok) {
				return res.json()
			} else {
				return {"error": "Fehler beim Abrufen der Matchups"};
			}
		})
		.then(data => {
			if (data.error) {
				return {"error": data.error};
			}
			return data;
		})
}