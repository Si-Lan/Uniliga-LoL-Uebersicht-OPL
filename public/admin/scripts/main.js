function set_button_listeners() {
	$(".get-teams").on("click", function () {get_teams_for_tournament(this.getAttribute("data-id"))});
	$(".get-teams-delete").on("click", function () {get_teams_for_tournament(this.getAttribute("data-id"),true)});
	$(".get-players").on("click", function () {get_players_for_tournament(this.getAttribute("data-id"))});
	$(".get-riotids").on("click", function () {get_riotids_for_tournament(this.getAttribute("data-id"))});
	$(".get-matchups").on("click", function () {get_matchups_for_tournament(this.getAttribute("data-id"))});
	$(".get-matchups-delete").on("click", function () {get_matchups_for_tournament(this.getAttribute("data-id"),true)});
	$(".get-results").on("click", function () {get_results_for_tournament(this.getAttribute("data-id"))});
	$(".get-results-unplayed").on("click", function () {get_results_for_tournament(this.getAttribute("data-id"),true)});
	$(".calculate-standings").on("click", function () {calculate_standings_from_matchups(this.getAttribute("data-id"))});
	$(".open-tournament-data-popup").on("click", function() {$(`dialog.tournament-data-popup.${this.getAttribute("data-id")}`)[0].showModal()});
	$(".toggle-turnierselect-accordeon").on("click", function () {
		toggle_turnier_select_accordeon(this.getAttribute("data-id"));
	});
}

function get_teams_for_tournament(tournamentID, deletemissing = false) {
	let button = deletemissing ? $(".get-teams-delete") : $(".get-teams");
	button.addClass("button-updating");
	button.prop("disabled", true);

	let del = deletemissing ? "true" : "false";

	fetch(`/admin/ajax/get-opl-data.php`, {
		method: "GET",
		headers: {
			"type": "get_teams_for_tournament",
			"id": tournamentID,
			"deletemissing": del,
		}
	})
		.then(res => res.json())
		.then(result => {
			console.log(result);
			button.prop("disabled", false);
			button.removeClass("button-updating");
		})
		.catch(e => console.error(e));
}

function get_players_for_tournament(tournamentID) {
	let button = $(".get-players");
	button.addClass("button-updating");
	button.prop("disabled", true);
	let loadingbar_width = 0;
	button.attr("style",`--loading-bar-width:${loadingbar_width}%`);

	fetch(`/api/get-data.php`, {
		method: "GET",
		headers: {
			"type": "teams",
			"tournamentid": tournamentID,
		}
	})
		.then(res => res.json())
		.then(async teams => {
			for (const team of teams) {
				await fetch(`/admin/ajax/get-opl-data.php`, {
					method: "GET",
					headers: {
						"type": "get_players_for_team",
						"teamID": team.OPL_ID,
						"tournamentID": tournamentID,
					}
				})
					.then(res => res.json())
					.then(result => {
						console.log(result);
					})
					.catch(e => console.error(e));
				loadingbar_width += 100/teams.length;
				button.attr("style",`--loading-bar-width:${loadingbar_width}%`);
				await new Promise(r => setTimeout(r, 1000));
			}
			button.prop("disabled", false);
			button.removeClass("button-updating");
		})
		.catch(e => console.error(e));
}

function get_summonerNames_for_tournament(tournamentID) {
	let button = $(".get-summoners");
	button.addClass("button-updating");
	button.prop("disabled", true);
	let loadingbar_width = 0;
	button.attr("style",`--loading-bar-width:${loadingbar_width}%`);

	fetch(`/api/get-data.php`, {
		method: "GET",
		headers: {
			"type": "teams",
			"tournamentid": tournamentID,
		}
	})
		.then(res => res.json())
		.then(async teams => {
			for (const team of teams) {
				await fetch(`/admin/ajax/get-opl-data.php`, {
					method: "GET",
					headers: {
						"type": "get_summonerNames_for_team",
						"teamID": team.OPL_ID,
						"tournamentID": tournamentID,
					}
				})
					.then(res => res.json())
					.then(result => {
						console.log(result);
					})
					.catch(e => console.error(e));
				loadingbar_width += 100/teams.length;
				button.attr("style",`--loading-bar-width:${loadingbar_width}%`);
				// no need to wait here, fetched php script sleeps for 1 sec at the end
			}
			button.prop("disabled", false);
			button.removeClass("button-updating");
		})
		.catch(e => console.error(e));
}
function get_riotids_for_tournament(tournamentID) {
	let button = $(".get-riotids");
	button.addClass("button-updating");
	button.prop("disabled", true);
	let loadingbar_width = 0;
	button.attr("style",`--loading-bar-width:${loadingbar_width}%`);

	fetch(`/api/get-data.php`, {
		method: "GET",
		headers: {
			"type": "teams",
			"tournamentid": tournamentID,
		}
	})
		.then(res => res.json())
		.then(async teams => {
			for (const team of teams) {
				await fetch(`/admin/ajax/get-opl-data.php`, {
					method: "GET",
					headers: {
						"type": "get_riotids_for_team",
						"teamID": team.OPL_ID,
						"tournamentID": tournamentID,
					}
				})
					.then(res => res.json())
					.then(result => {
						console.log(result);
					})
					.catch(e => console.error(e));
				loadingbar_width += 100/teams.length;
				button.attr("style",`--loading-bar-width:${loadingbar_width}%`);
				// no need to wait here, fetched php script sleeps for 1 sec at the end
			}
			button.prop("disabled", false);
			button.removeClass("button-updating");
		})
		.catch(e => console.error(e));
}

function get_matchups_for_tournament(tournamentID, deletemissing = false) {
	let button = deletemissing ? $(".get-matchups-delete") : $(".get-matchups");
	button.addClass("button-updating");
	button.prop("disabled", true);

	let del = deletemissing ? "true" : "false";

	fetch(`/admin/ajax/get-opl-data.php`, {
		method: "GET",
		headers: {
			"type": "get_matchups_for_tournament",
			"id": tournamentID,
			"deletemissing": del,
		}
	})
		.then(res => res.json())
		.then(result => {
			console.log(result);
			button.prop("disabled", false);
			button.removeClass("button-updating");
		})
		.catch(e => console.error(e));
}

function get_results_for_tournament(tournamentID,unplayed_only=false) {
	let button = unplayed_only ? $(".get-results-unplayed") : $(".get-results");
	button.addClass("button-updating");
	button.prop("disabled", true);
	let loadingbar_width = 0;

	let matchup_headers = {
		"type": "matchups",
		"tournamentid": tournamentID,
	}
	if (unplayed_only) matchup_headers["unplayedonly"] = "true";

	fetch(`/api/get-data.php`, {
		method: "GET",
		headers: matchup_headers
	})
		.then(res => res.json())
		.then(async matches => {
			for (const match of matches) {
				await fetch(`/admin/ajax/get-opl-data.php`, {
					method: "GET",
					headers: {
						"type": "get_results_for_matchup",
						"id": match.OPL_ID,
					}
				})
					.then(res => res.json())
					.then(result => {
						console.log(result);
					})
					.catch(e => console.error(e));
				loadingbar_width += 100/matches.length;
				button.attr("style",`--loading-bar-width:${loadingbar_width}%`);
				await new Promise(r => setTimeout(r, 1000));
			}
			button.prop("disabled", false);
			button.removeClass("button-updating");
		})
		.catch(e => console.error(e));
}

function calculate_standings_from_matchups(tournamentID) {
	let button = $(".calculate-standings");
	button.addClass("button-updating");
	button.prop("disabled", true);

	fetch(`/admin/ajax/get-opl-data.php`, {
		method: "GET",
		headers: {
			"type": "calculate_standings_from_matchups",
			"id": tournamentID,
		}
	})
		.then(res => res.json())
		.then(result => {
			console.log(result);
			button.prop("disabled", false);
			button.removeClass("button-updating");
		})
		.catch(e => console.error(e));
}



// ddragon update
function sync_patches_to_db(button) {
	$(button).addClass("button-updating");
	button.disabled = true;
	fetch(`/admin/ajax/ddragon-update.php`, {
		method: "POST",
		headers: {
			"type": "sync_patches_to_db"
		}
	})
		.then(res => res.json())
		.then(updates => {
			$(button).removeClass("button-updating");
			button.disabled = false;
			let added_patches = (updates["added"] === 0) ? "" : `<br>added Patches: ${updates["added"]}`;
			$('dialog.patch-result-popup .dialog-content').html(`deleted Patches: ${updates["deleted"]}<br>changed Patches: ${updates["updated"].length}${added_patches}`);
			$('dialog.patch-result-popup')[0].showModal();
			regenerate_patch_rows();
		})
		.catch(e => console.error(e));
}
$(document).ready(function () {
	$(".sync_patches").on("click", function () {
		sync_patches_to_db(this);
	});
});
function download_ddragon_data(button) {
	let patch = button.getAttribute("data-patch");
	$(button).addClass("button-updating");
	button.disabled = true;
	fetch(`/admin/ajax/ddragon-update.php`, {
		method: "POST",
		headers: {
			"type": "jsons_for_patch",
			"patch": patch,
		}
	})
		.then(res => res.text())
		.then(updates => {
			$(button).removeClass("button-updating");
			button.disabled = false;
			update_patchdata_status(patch);
		})
		.catch(e => console.error(e));
}
$(document).ready(function () {
	$(".patch-update.json").on("click", function () {
		download_ddragon_data(this);
	});
});
function add_new_patch(button) {
	let patch = button.getAttribute("data-patch");
	$(button).addClass("button-updating");
	button.disabled = true;
	fetch(`/admin/ajax/ddragon-update.php`, {
		method: "POST",
		headers: {
			"type": "add_new_patch",
			"patch": patch,
		}
	})
		.then(res => res.text())
		.then(updates => {
			$(button).removeClass("button-updating");
			button.disabled = false;
			$(button).parent().remove();
			regenerate_patch_rows();
		})
		.catch(e => console.error(e));
}
$(document).ready(function () {
	$(".add_patch").on("click", function () {
		add_new_patch(this);
	});
});
function download_ddragon_images(button) {
	let patch = button.getAttribute("data-patch");
	let type = button.getAttribute("data-getimg");
	let force = $('#force-overwrite-patch-img')[0].checked;
	$(button).addClass("button-updating");
	button.disabled = true;
	$(`button.patch-update[data-patch="${patch}"]`).prop("disabled","true");

	fetch(`/admin/ajax/ddragon-update.php`, {
		method: "GET",
		headers: {
			type: "get_image_data",
			patch: patch,
			imagetype: type,
			onlymissing: (!force).toString(),
		}
	})
		.then(res => res.json())
		.then(async images => {
			let loadingbar_width = 0;
			let imgs_gotten = 0;
			if (images.length === 0) {
				$(button).removeClass("button-updating");
				button.disabled = false;
				$(`button.patch-update[data-patch="${patch}"]`).prop("disabled","");
			} else {
				loadingbar_width = 1;
				button.style.setProperty("--loading-bar-width", `${loadingbar_width}%`);
			}
			for (const image of images) {
				fetch(`/admin/ajax/ddragon-update.php`, {
					method: "POST",
					headers: {
						type: "download_dd_img",
						imgsource: image["source"],
						targetdir: image["target_dir"],
						targetname: image["target_name"],
						forcedownload: force.toString(),
					}
				})
					.then(res => res.text())
					.then(location => {
						loadingbar_width += 99 / images.length;
						button.style.setProperty("--loading-bar-width", `${loadingbar_width}%`);
						imgs_gotten++;
						//console.log(location);
						if (imgs_gotten >= images.length) {
							$(button).removeClass("button-updating");
							button.disabled = false;
							$(`button.patch-update[data-patch="${patch}"]`).prop("disabled","");
							loadingbar_width = 0;
							button.style.setProperty("--loading-bar-width", "0");
							fetch(`/admin/ajax/ddragon-update.php`, {
								method: "POST",
								headers: {
									type: "sync_patches_to_db",
									patch: patch,
								}
							})
								.then(() => {
									update_patchdata_status(patch);
								})
								.catch(e => console.error(e));
						}
					})
					.catch(e => console.error(e));
				await new Promise(r => setTimeout(r, 500));
			}
		})
		.catch(e => console.error(e));
}
$(document).ready(function () {
	$(".patch-update[data-getimg]").on("click", function () {
		download_ddragon_images(this);
	});
});
function delete_old_ddragon_pngs(button) {
	let patch = button.parentElement.parentElement.parentElement.getAttribute("data-patch");
	$(button).addClass("button-updating");
	button.disabled = true;

	fetch(`/admin/ajax/ddragon-update.php`, {
		method: "POST",
		headers: {
			type: "delete_ddragon_pngs",
			patch: patch,
		}
	})
		.then(() => {
			$(button).removeClass("button-updating");
			button.disabled = false;
		})
		.catch(e => console.error(e));
}
$(document).ready(function () {
	$(".patch-remove-pngs").on("click", function () {
		delete_old_ddragon_pngs(this);
	});
});
function regenerate_patch_rows() {
	fetch(`/admin/ajax/ddragon-update.php`, {
		method: "GET",
		headers: {
			type: "get-patch-rows",
		}
	})
		.then(res => res.text())
		.then(rows => {
			$('.patch-row').remove();
			$('.patch-table').append(rows);
			// set eventListeners for newly added elements
			$(".patch-update.json").on("click", function () {download_ddragon_data(this)});
			$(".patch-update[data-getimg]").on("click", function () {download_ddragon_images(this)});
			$('button.patch-more-options').on('click', function() {
				let patch = this.getAttribute("data-patch");
				$(`dialog.patch-more-popup[data-patch="${patch}"]`)[0].showModal();
			});
			$('dialog.dismissable-popup').on('click', function (event) {
				if (event.target === this) {
					this.close();
				}
			});
		})
		.catch(e => console.error(e));
}
function update_patchdata_status(patch= "all") {
	fetch(`/api/get-data.php`, {
		method: "GET",
		headers: {
			type: "local_patch_info",
			patch: patch,
		}
	})
		.then(res => res.json())
		.then(patches => {
			for (const patch of patches) {
				let buttons = $(`.patchdata-status[data-patch="${patch["patch"]}"]`);
				for (const button of buttons) {
					if (button.classList.contains("json")) {
						(patch["data"]) ? button.setAttribute("data-status","true") : button.setAttribute("data-status","false");
					}
					if (button.classList.contains("all-img")) {
						(patch["champion_webp"] && patch["item_webp"] && patch["spell_webp"] && patch["runes_webp"]) ? button.setAttribute("data-status","true") : button.setAttribute("data-status","false");
					}
					if (button.classList.contains("champion-img")) {
						(patch["champion_webp"]) ? button.setAttribute("data-status","true") : button.setAttribute("data-status","false");
					}
					if (button.classList.contains("item-img")) {
						(patch["item_webp"]) ? button.setAttribute("data-status","true") : button.setAttribute("data-status","false");
					}
					if (button.classList.contains("spell-img")) {
						(patch["spell_webp"]) ? button.setAttribute("data-status","true") : button.setAttribute("data-status","false");
					}
					if (button.classList.contains("runes-img")) {
						(patch["runes_webp"]) ? button.setAttribute("data-status","true") : button.setAttribute("data-status","false");
					}
				}
			}
		})
}
$(document).ready(function () {
	$('button.patch-more-options').on('click', function() {
		let patch = this.getAttribute("data-patch");
		$(`dialog.patch-more-popup[data-patch="${patch}"]`)[0].showModal();
	});
	$('button.open_add_patch_popup').on('click', function() {
		$('dialog.add-patch-popup')[0].showModal();
	});

	$('dialog.dismissable-popup').on('click', function (event) {
		if (event.target === this) {
			this.close();
		}
	});
});

function toggle_maintenance_mode(turn_on = true) {
	const confirmtext = turn_on ? "Wartungsmodus aktivieren?" : "Wartungsmodus deaktivieren?";
	let cont = confirm(confirmtext);
	if (!cont) return;

	let button = $('button#maintenance-mode');
	let turn = turn_on ? "on" : "off";

	let turn_opposite = turn_on ? "off" : "on";
	button.addClass(`maintenance-${turn}`);

	button.removeClass(`maintenance-${turn_opposite}`);

	fetch("/admin/ajax/maintenance.php", {
		method: "GET",
		headers: {
			turn: turn,
		}
	})
		.catch(e => console.error(e));
}
$(document).ready(function () {
	$('button#maintenance-mode').on('click', function() {
		let turn = $(this).hasClass("maintenance-off");
		toggle_maintenance_mode(turn);
	});

});


async function update_all_teams() {
	let button = $(this);
	button.addClass("button-updating");
	button.prop("disabled", true);
	let loadingbar_width = 0;
	button.attr("style",`--loading-bar-width:${loadingbar_width}%`);

	let wrapper = $(".result-wrapper.gen-admin");
	let container = $(".result-wrapper.gen-admin .result-content");

	let teamlist;

	await fetch(`/api/get-data.php`, {
		method: "GET",
		headers: {
			"type": "teams",
		}
	})
		.then(res => res.json())
		.then(teams => {
			teamlist = teams;
		})
		.catch(e => console.error(e));

	add_to_write_results(wrapper,container, `----- ${teamlist.length} Teams gefunden ----- <br>`);
	console.log(teamlist);

	for (const teamIndex in teamlist) {
		let i = parseInt(teamIndex);
		let team = teamlist[i];

		await fetch(`/admin/ajax/get-opl-data.php`, {
			method: "GET",
			headers: {
				"type": "update_team",
				"teamid": team["OPL_ID"],
			}
		})
			.then(res => res.json())
			.then(result => {
				let team_result = `#${i+1}<br>Team: ${team["name"]}:<br>`;
				if (result.length === 0 || result["updated"].length === 0) {
					team_result += "<span style='color: orangered'>Keine Änderungen</span>";
				} else {
					for (const [updatetype,updated] of Object.entries(result["updated"])) {
						team_result += `<span style="color: orange">${updatetype}: \"${updated["old"]}\" => \"${updated["new"]}\"</span>`;
					}
				}
				if (result["logo_downloaded"]) team_result += "<span style='color: orange'>- Logo aktualisiert</span>";
				if (result["player_updates"].length !== 0) {
					team_result += "<span>Spieler:</span>";
					for (const player of result["player_updates"]) {
						if (player["written"]) {
							team_result += `<span style='color: limegreen'>NEU: ${player["player"]["name"]} (${player["player"]["OPL_ID"]})</span>`;
						} else if (player["updated"].length !== 0) {
							team_result += `<span style='color: orange'>UPDATED: ${player["player"]["name"]} (${player["player"]["OPL_ID"]})</span>`;
							for (const [updatetype,updated] of Object.entries(player["updated"])) {
								team_result += `<span style='color: orange'>- ${updatetype}: ${updated["old"]} => ${updated["new"]}</span>`;
							}
						} else {
							team_result += `<span style="color: cornflowerblue">${player["player"]["name"]} (${player["player"]["OPL_ID"]})</span>`;
						}
					}
				}
				if (result["players_removed"].length !== 0) {
					team_result += "<span>entfernte Spieler:</span>";
					for (const player of result["players_removed"]) {
						team_result += `<span style='color: orangered'>ALT: ${player["name"]} (${player["OPL_ID"]})</span>`;
					}
				}
				team_result += "<br>";
				add_to_write_results(wrapper,container,team_result);
				loadingbar_width += 100/teamlist.length;
				button.attr("style",`--loading-bar-width:${loadingbar_width}%`);
			})
			.catch(e => {console.error(e)});

		await new Promise(r => setTimeout(r, 1000));
	}

	add_to_write_results(wrapper,container,"<br>----- Done with updating Teams -----<br>");

	button.prop("disabled", false);
	button.removeClass("button-updating");
	button.attr("style",`--loading-bar-width: 0`);
}

async function get_ranks_for_all_players() {
	//console.log("----- Start getting Ranks (all) -----");
	let button = $(this);
	button.addClass("button-updating");
	button.prop("disabled", true);
	let loadingbar_width = 0;
	button.attr("style",`--loading-bar-width:${loadingbar_width}%`);

	let wrapper = $(".result-wrapper.gen-admin");
	let container = $(".result-wrapper.gen-admin .result-content");

	let playerlist;

	await fetch(`/api/get-data.php`, {
		method: "GET",
		headers: {
			"type": "players",
			"summonerIDset": "true",
		},
	})
		.then(res => res.json())
		.then(players => {
			playerlist = players;
		})
		.catch(e => console.error(e));

	add_to_write_results(wrapper,container,"----- "+playerlist.length+" Spieler gefunden -----<br>");

	for (const playerIndex in playerlist) {
		let i = parseInt(playerIndex)
		let player = playerlist[i];
		//console.log("Starting with Player "+(i+1));

		fetch(`/admin/ajax/get-rgapi-data.php`, {
			method: "GET",
			headers: {
				"type": "get-rank-for-player",
				"player": player["OPL_ID"],
				"update-current-team": "true",
			}
		})
			.then(res => res.text())
			.then(result => {
				//console.log(`Player ${i+1} done`);
				//console.log(result);
				add_to_write_results(wrapper,container,`#${i+1}<br>${result}`);
				loadingbar_width += 100/playerlist.length;
				button.attr("style",`--loading-bar-width:${loadingbar_width}%`);
			})
			.catch(e => console.error(e));

		if ((i+1) % 50 === 0) {
			//console.log("-- sleep (#"+ (i+1) +") --");
			// added some more seconds as padding to avoid 429 errors
			for (let t = 0; t <= 15; t++) {
				await new Promise(r => setTimeout(r, 1000));
				add_to_write_results(wrapper,container,`----- wait ${15-t} -----<br>`);
			}
			//console.log("-- slept --");
		}
	}

	//console.log("----- Done with getting Ranks (all) -----");
	add_to_write_results(wrapper,container,"<br>----- Done with getting Ranks -----<br>");

	button.prop("disabled", false);
	button.removeClass("button-updating");
	button.attr("style",`--loading-bar-width: 0`);
}
$(document).ready(function () {
	$('button.update_all_player_ranks').on('click', get_ranks_for_all_players);
	$('button.update_all_teams').on('click', update_all_teams);
});

function clear_results(ID) {
	let results = $(".result-wrapper."+ID);
	if (!results.hasClass("no-res")) {
		results.addClass("no-res");
		results.find(".result-content").html("");
	}
}
function add_to_write_results(wrapper_element, content_element, content) {
	wrapper_element.removeClass('no-res');
	content_element.append(content);
	if (content_element.prop("scrollHeight") - content_element.scrollTop() < 1000) {
		content_element.scrollTop(content_element.prop("scrollHeight"));
	}
}