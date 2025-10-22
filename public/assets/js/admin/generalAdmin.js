function toggle_maintenance_mode(turn_on = true) {
	const confirmtext = turn_on ? "Wartungsmodus aktivieren?" : "Wartungsmodus deaktivieren?";
	let continu = confirm(confirmtext);
	if (!continu) return;

	let button = $('button#maintenance-mode');
	let turn = turn_on ? "on" : "off";

	let turn_opposite = turn_on ? "off" : "on";
	button.addClass(`maintenance-${turn}`);

	button.removeClass(`maintenance-${turn_opposite}`);

	fetch(`/admin/api/maintenance/${turn}`, {method: "PUT"})
		.catch(e => console.error(e));
}
$(document).on("click", "button#maintenance-mode", function () {
	let turn = $(this).hasClass("maintenance-off");
	toggle_maintenance_mode(turn);
})


async function update_team(teamId) {
	return await fetch(`/admin/api/opl/teams/${teamId}/update`, {method: "POST"})
		.then(res => {
			if (res.ok) {
				return res.json()
			} else {
				return {"error": "Fehler beim Aktualisieren des Teams"};
			}
		})
		.then(data => {
			if (data.error) {
				return {"error": data.error};
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

			return `Team ${data.team?.team?.name} (${data.team?.team?.id})<br>
					- Team ${data.team?.result}<br>
					- Spieler: ${playerCount}<br>
					-- ${addedPlayersToTeam.length} Spieler zum Team hinzugefügt<br>
					-- ${removedPlayersFromTeam.length} Spieler aus Team entfernt<br>
					-- (${unchangedPlayers.length} allgemein unverändert)<br>
					-- (${addedPlayers.length} allgemein neu)<br>
					-- (${updatedPlayers.length} allgemein aktualisiert)<br>`;
		})
}
$(document).on("click", "button.update_all_teams", async function () {
	const jqButton = $(this);
	setButtonUpdating(jqButton);
	addToGeneralResults("Aktualisiere alle Teams");

	const teams = await getAllTeams();
	if (teams.error) {
		addToGeneralResults(`Fehler beim Abrufen der Teams: ${teams.error}`);
		unsetButtonUpdating(jqButton);
		return;
	}
	addToGeneralResults(`Aktualisiere ${teams.length} Teams`);

	let count = 0;
	for (const team of teams) {
		count++;
		addToGeneralResults(`Aktualisiere Team ${team.id} (${count}/${teams.length})`);
		const updateResult = await update_team(team.id);
		addToGeneralResults(updateResult);
		setButtonLoadingBarWidth(jqButton, Math.round(count / teams.length * 100));
		await new Promise(r => setTimeout(r, 1000));
	}
	unsetButtonUpdating(jqButton);
})

async function getAllTeams() {
	return await fetch(`/api/teams`)
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
		.catch(error => {
			console.error(error);
			return {"error": "Fehler beim Abrufen der Teams"};
		})
}



/* --------------------------------------------------- */
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

function addToGeneralResults(content) {
	$(".result-wrapper.gen-admin").removeClass('no-res');
	const container = $(".result-wrapper.gen-admin .result-content");
	container.append(content+"<br>");
	if (container.prop("scrollHeight") - container.scrollTop() < 1000) {
		container.scrollTop(container.prop("scrollHeight"));
	}
}
function clearGeneralResults() {
	const wrapper = $(".result-wrapper.gen-admin");
	if (!wrapper.hasClass('no-res')) {
		wrapper.addClass('no-res');
		wrapper.find('.result-content').html('');
	}
}