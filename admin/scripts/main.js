function set_button_listeners() {
	$(".update_tournament").on("click", function () {write_tournament(this.getAttribute("data-id"))});
	$(".get_event_children").on("click", function () {open_related_events_popup(this.getAttribute("data-id"),"children")});
	$(".get_event_parents").on("click", function () {open_related_events_popup(this.getAttribute("data-id"),"parents")});
	$(".get-teams").on("click", function () {get_teams_for_tournament(this.getAttribute("data-id"))});
	$(".get-teams-delete").on("click", function () {get_teams_for_tournament(this.getAttribute("data-id"),true)});
	$(".get-players").on("click", function () {get_players_for_tournament(this.getAttribute("data-id"))});
	$(".get-riotids").on("click", function () {get_riotids_for_tournament(this.getAttribute("data-id"))});
	$(".get-matchups").on("click", function () {get_matchups_for_tournament(this.getAttribute("data-id"))});
	$(".get-matchups-delete").on("click", function () {get_matchups_for_tournament(this.getAttribute("data-id"),true)});
	$(".get-results").on("click", function () {get_results_for_tournament(this.getAttribute("data-id"))});
	$(".calculate-standings").on("click", function () {calculate_standings_from_matchups(this.getAttribute("data-id"))});
	$(".open-tournament-data-popup").on("click", function() {$(`dialog.tournament-data-popup.${this.getAttribute("data-id")}`)[0].showModal()});
	$(".toggle-turnierselect-accordeon").on("click", function () {
		toggle_turnier_select_accordeon(this.getAttribute("data-id"));
	});
}

$(document).ready(() => {
	$("#turnier-button-get").on("click", get_tournament);
	$("#input-tournament-id").on("keydown", (event) => {
		if (event.key === "Enter") get_tournament();
	});
	$(".write_tournament").on("click", () => write_tournament());
	set_button_listeners();
});

function create_tournament_buttons() {
	let ref_button = document.getElementsByClassName('refresh-tournaments')[0] ?? null;
	if (ref_button != null) {
		ref_button.innerHTML = "Refreshing...";
	}
	const open_accordeons = sessionStorage.getItem("open_admin_accordeons");
	fetch(`./admin/ajax/create_admin_buttons.php`, {
		method: "GET",
		headers: {
			"open-accordeons": open_accordeons,
		}
	})
		.then(res => res.text())
		.then(content => {
			document.getElementsByClassName("turnier-select")[0].innerHTML = content;
			$('dialog.dismissable-popup').on('click', function (event) {if (event.target === this) this.close()});
			set_button_listeners();
		})
		.catch(e => console.error(e));
}

function get_tournament() {
	const id = $("#input-tournament-id").val();
	fetch(`./admin/ajax/get-opl-data.php`, {
		method: "GET",
		headers: {
			"type": "get_tournament",
			"id": id,
		}
	})
		.then(res => res.json())
		.then(result => {
			$("dialog#tournament-add .dialog-content .tournament-write-data-wrapper").remove();
			$("dialog#tournament-add .dialog-content").append(result.button);
			$("dialog#tournament-add")[0].showModal();
			$(".write_tournament").on("click", () => write_tournament());
		})
		.catch(e => console.error(e));
}

function write_tournament(tournamentID = null, from_related = false) {
	let id_class = (tournamentID === null) ? "write-popup" : tournamentID;
	let additional_related = (from_related) ? "dialog#related-add" : "";
	let data = {};
	data.OPL_ID = $(`${additional_related} .${id_class} label.write_tournament_id input`).val();
	data.OPL_ID_parent = $(`${additional_related} .${id_class} label.write_tournament_parent input`).val();
	data.OPL_ID_top_parent = $(`${additional_related} .${id_class} label.write_tournament_top_parent input`).val();
	data.name = $(`${additional_related} .${id_class} label.write_tournament_name input`).val();
	data.split = $(`${additional_related} .${id_class} label.write_tournament_split select`).find(":selected").val();
	data.season = $(`${additional_related} .${id_class} label.write_tournament_season input`).val();
	data.eventType = $(`${additional_related} .${id_class} label.write_tournament_type select`).find(":selected").val();
	data.format = $(`${additional_related} .${id_class} label.write_tournament_format select`).find(":selected").val();
	data.number = $(`${additional_related} .${id_class} label.write_tournament_number input`).val();
	data.numberRangeTo = $(`${additional_related} .${id_class} label.write_tournament_number2 input`).val();
	data.dateStart = $(`${additional_related} .${id_class} label.write_tournament_startdate input`).val();
	data.dateEnd = $(`${additional_related} .${id_class} label.write_tournament_enddate input`).val();
	data.OPL_logo_url = $(`${additional_related} .${id_class} label.write_tournament_logourl input`).val();
	data.OPL_ID_logo = $(`${additional_related} .${id_class} label.write_tournament_logoid input`).val();
	data.finished = $(`${additional_related} .${id_class} label.write_tournament_finished input`).prop("checked") ? 1 : 0;
	data.deactivated = !$(`${additional_related} .${id_class} label.write_tournament_show input`).prop("checked") ? 1 : 0;
	data.archived = $(`${additional_related} .${id_class} label.write_tournament_archived input`).prop("checked") ? 1 : 0;
	data.ranked_season = $(`${additional_related} .${id_class} label.write_tournament_ranked_season input`).val();
	data.ranked_split = $(`${additional_related} .${id_class} label.write_tournament_ranked_split input`).val();
	console.log(data);
	fetch(`./admin/ajax/get-opl-data.php`, {
		method: "GET",
		headers: {
			"type": "write_tournament",
			"data": JSON.stringify(data),
		}
	})
		.then(res => res.text())
		.then(result => {
			const dialog_content = $("dialog.write-result-popup .dialog-content");
			dialog_content.html("");
			dialog_content.append(result);
			$("dialog.write-result-popup")[0].showModal();
		})
		.catch(e => console.error(e));
}

let related_events_fetch_control = null;
$(document).ready(() => {
	$('dialog#related-add').on("close", () => {related_events_fetch_control.abort()});
});
async function open_related_events_popup(tournamentID, relation = "children") {
	let popup = $('#related-add');
	let get_children;
	if (relation === "children") {
		get_children = true;
	} else if (relation === "parents") {
		get_children = false;
	} else {
		return;
	}
	/* reset dialog */
	if (related_events_fetch_control !== null) related_events_fetch_control.abort();
	related_events_fetch_control = new AbortController();
	popup.find(".dialog-content > *:not(.close-popup,.close-button-space)").remove();

	popup[0].showModal();
	popup.find(".close-button-space").append("<div class='popup-loading-indicator'></div>");
	let popup_content = popup.find(".dialog-content");
	let tournament_name = $(`.tournament-write-data.${tournamentID} label.write_tournament_name input`)[0].value;
	if (get_children) {
		popup_content.append(`<h2>Kinder von "${tournament_name}"</h2>`);
	} else {
		popup_content.append(`<h2>Eltern von "${tournament_name}"</h2>`);
	}

	let fetchtype = (get_children) ? "get_event_children" : "get_event_parents";
	let related_events;

	/* get children and create Buttons with ID */
	await fetch(`./admin/ajax/get-opl-data.php`, {
		method: "GET",
		headers: {
			"type": fetchtype,
			"id": tournamentID,
		},
		signal: related_events_fetch_control.signal,
	})
		.then(res => res.json())
		.then(result => {
			related_events = result;
			popup_content.append("<div class='related-event-button-list'></div>");
			let list = popup_content.find(".related-event-button-list");
			for (const event of related_events) {
				list.append(`<button data-id="${event[0]}" class="${(event[1])?"in-db":""}" disabled>${event[0]}</button>`);
			}
		})
		.catch(e => console.error(e));

	/* add tournamentname to buttons */
	let breaker = false;
	for (const event of related_events) {
		await fetch(`./admin/ajax/get-opl-data.php`, {
			method: "GET",
			headers: {
				"type": "get_tournament",
				"id": event[0],
			},
			signal: related_events_fetch_control.signal,
		})
			.then(res => res.json())
			.then(result => {
				let button = popup_content.find(`button[data-id=${event[0]}]`);
				let tournament_name, tournament404 = false;
				if ((result["response"]??"")==="404") {
					tournament_name = "'Tournament nicht öffentlich'";
					tournament404 = true;
				} else {
					tournament_name = result["data"]["name"];
				}
				button.html(`${event[0]}: ${tournament_name}`);

				button.after(`<dialog class="tournament-add ${event[0]}">
								<div class="dialog-content">
                					<button class="close-popup"><span class="material-symbol">${get_material_icon("close",true)}</span></button>
                					<div class="close-button-space"></div>
                				</div>
                			</dialog>`);
				let dialog = $(`dialog.tournament-add.${event[0]}`);
				dialog.find(".close-popup").on("click", function() {this.closest("dialog").close()});
				button.on("click", () => {$(`.tournament-add.${event[0]}`)[0].showModal()});

				if (!tournament404) {
					dialog.find(".dialog-content").append(result.button);
					dialog.find(".write_tournament").on("click", () => write_tournament(event[0],true));
					button.removeAttr("disabled");
				}
			})
			.catch(e => {
				if (e.name === "AbortError") {
					console.warn(e);
					breaker = true;
				} else {
					console.error(e);
				}
			});
		if (breaker) break;
	}
	popup.find(".popup-loading-indicator").remove();
}

function get_teams_for_tournament(tournamentID, deletemissing = false) {
	let button = deletemissing ? $(".get-teams-delete") : $(".get-teams");
	button.addClass("button-updating");
	button.prop("disabled", true);

	let del = deletemissing ? "true" : "false";

	fetch(`./admin/ajax/get-opl-data.php`, {
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

	fetch(`./ajax/get-data.php`, {
		method: "GET",
		headers: {
			"type": "teams",
			"tournamentid": tournamentID,
		}
	})
		.then(res => res.json())
		.then(async teams => {
			for (const team of teams) {
				await fetch(`./admin/ajax/get-opl-data.php`, {
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

	fetch(`./ajax/get-data.php`, {
		method: "GET",
		headers: {
			"type": "teams",
			"tournamentid": tournamentID,
		}
	})
		.then(res => res.json())
		.then(async teams => {
			for (const team of teams) {
				await fetch(`./admin/ajax/get-opl-data.php`, {
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

	fetch(`./ajax/get-data.php`, {
		method: "GET",
		headers: {
			"type": "teams",
			"tournamentid": tournamentID,
		}
	})
		.then(res => res.json())
		.then(async teams => {
			for (const team of teams) {
				await fetch(`./admin/ajax/get-opl-data.php`, {
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

	fetch(`./admin/ajax/get-opl-data.php`, {
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

function get_results_for_tournament(tournamentID) {
	let button = $(".get-results");
	button.addClass("button-updating");
	button.prop("disabled", true);
	let loadingbar_width = 0;

	fetch(`./ajax/get-data.php`, {
		method: "GET",
		headers: {
			"type": "matchups",
			"tournamentid": tournamentID,
		}
	})
		.then(res => res.json())
		.then(async matches => {
			for (const match of matches) {
				await fetch(`./admin/ajax/get-opl-data.php`, {
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

	fetch(`./admin/ajax/get-opl-data.php`, {
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

function toggle_turnier_select_accordeon(tournamentID) {
	$(".turnier-sl-accordeon."+tournamentID).toggleClass("open");
	$(`.toggle-turnierselect-accordeon[data-id=${tournamentID}]`).toggleClass("open");

	const open_accordeons = $(`.toggle-turnierselect-accordeon.open`);
	let open_accordeon_ids = [];
	open_accordeons.each(function() {open_accordeon_ids.push(this.getAttribute("data-id"))})
	sessionStorage.setItem("open_admin_accordeons",JSON.stringify(open_accordeon_ids));
}

// ddragon update
function sync_patches_to_db(button) {
	$(button).addClass("button-updating");
	button.disabled = true;
	fetch(`admin/ajax/ddragon-update.php`, {
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
	fetch(`admin/ajax/ddragon-update.php`, {
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
	fetch(`admin/ajax/ddragon-update.php`, {
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

	fetch(`admin/ajax/ddragon-update.php`, {
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
				fetch(`admin/ajax/ddragon-update.php`, {
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
							fetch(`admin/ajax/ddragon-update.php`, {
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

	fetch(`admin/ajax/ddragon-update.php`, {
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
	fetch(`admin/ajax/ddragon-update.php`, {
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
	fetch(`ajax/get-data.php`, {
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

	fetch("./admin/ajax/maintenance.php", {
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

	await fetch(`./ajax/get-data.php`, {
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

		await fetch(`./admin/ajax/get-opl-data.php`, {
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

	await fetch(`./ajax/get-data.php`, {
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

		fetch(`./admin/ajax/get-rgapi-data.php`, {
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

function add_new_ranked_split() {
	$('.ranked-split-list .button-row').before(`
		<div class='ranked-split-edit ranked_split_write'>
			<label class="write_ranked_split_season">Season<input type="text"></label>
			<label class="write_ranked_split_split">Split<input type="text"></label>
			<label class="write_ranked_split_startdate">Start<input type="date"></label>
			<label class="write_ranked_split_enddate">Ende<input type="date"></label>
			<button class='sec-button save_ranked_split' title='Speichern'><div class='material-symbol'><svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px"><path d="M180-120q-24 0-42-18t-18-42v-600q0-24 18-42t42-18h478q12.44 0 23.72 5T701-822l121 121q8 8 13 19.28 5 11.28 5 23.72v478q0 24-18 42t-42 18H180Zm600-536L656-780H180v600h600v-476ZM479.76-245q43.24 0 73.74-30.26 30.5-30.27 30.5-73.5 0-43.24-30.26-73.74-30.27-30.5-73.5-30.5-43.24 0-73.74 30.26-30.5 30.27-30.5 73.5 0 43.24 30.26 73.74 30.27 30.5 73.5 30.5ZM263-584h298q12.75 0 21.38-8.63Q591-601.25 591-614v-83q0-12.75-8.62-21.38Q573.75-727 561-727H263q-12.75 0-21.37 8.62Q233-709.75 233-697v83q0 12.75 8.63 21.37Q250.25-584 263-584Zm-83-72v476-600 124Z"/></svg></div></button>
			<button class='sec-button delete_ranked_split' title='Löschen'><div class='material-symbol'><svg xmlns="http://www.w3.org/2000/svg" height="48" viewBox="0 96 960 960" width="48"><path d="M480 618 270 828q-9 9-21 9t-21-9q-9-9-9-21t9-21l210-210-210-210q-9-9-9-21t9-21q9-9 21-9t21 9l210 210 210-210q9-9 21-9t21 9q9 9 9 21t-9 21L522 576l210 210q9 9 9 21t-9 21q-9 9-21 9t-21-9L480 618Z"/></svg></div></button>
		</div>
	`);
	$('.ranked-split-list .ranked_split_write button.delete_ranked_split').eq(-1).on('click', function() {$(this).parent().remove()});
	$('.ranked-split-list .ranked_split_write button.save_ranked_split').eq(-1).on('click', save_new_ranked_split);
}
async function save_new_ranked_split() {
	let row = $(this).parent();
	let season = row.find(".write_ranked_split_season input").eq(0).val();
	let split = row.find(".write_ranked_split_split input").eq(0).val();
	let start = row.find(".write_ranked_split_startdate input").eq(0).val();
	let end = row.find(".write_ranked_split_enddate input").eq(0).val();

	await fetch(`./admin/ajax/db-changes.php`, {
		method: "POST",
		headers: {
			type: "add_split",
			season: season,
			split: split,
			start: start,
			end: end,
		}
	})
		.catch(e => console.error(e));

	await fetch(`./admin/ajax/create-page-elements.php`, {
		method: "GET",
		headers: {
			type: "ranked_split_rows",
		}
	})
		.then(res => res.text())
		.then(new_rows => {
			let list = $(".ranked-split-list");
			let old_rows = list.find(".ranked-split-edit").not(".ranked_split_write");
			old_rows.remove();
			list.prepend(new_rows);
			$("div.ranked-split-edit input").on("change", detect_input_change);
			$("div.ranked-split-edit .reset_inputs").on("click", reset_inputs);
			$("div.ranked-split-edit .delete_ranked_split").on("click", delete_ranked_split);
		})
		.catch(e => console.error(e));

	row.remove();
}
async function save_ranked_splits() {
	let list = $(".ranked-split-list");
	let all_rows = list.find(".ranked-split-edit").not(".ranked_split_write");
	let edited_rows = list.find(".ranked-split-edit").not(".ranked_split_write").has("input.input_changed");
	for (const row of edited_rows) {
		let rowJQ = $(row);
		let season = rowJQ.find(".write_ranked_split_season input").eq(0).val();
		let split = rowJQ.find(".write_ranked_split_split input").eq(0).val();
		let start = rowJQ.find(".write_ranked_split_startdate input").eq(0).val();
		let end = rowJQ.find(".write_ranked_split_enddate input").eq(0).val();
		await fetch(`./admin/ajax/db-changes.php`, {
			method: "POST",
			headers: {
				type: "update_split",
				season: season,
				split: split,
				start: start,
				end: end,
			}
		})
			.catch(e => console.error(e));
	}

	await fetch(`./admin/ajax/create-page-elements.php`, {
		method: "GET",
		headers: {
			type: "ranked_split_rows",
		}
	})
		.then(res => res.text())
		.then(new_rows => {
			all_rows.remove();
			list.prepend(new_rows);
			$("div.ranked-split-edit input").on("change", detect_input_change);
			$("div.ranked-split-edit .reset_inputs").on("click", reset_inputs);
			$("div.ranked-split-edit .delete_ranked_split").on("click", delete_ranked_split);
		})
		.catch(e => console.error(e));
}
async function delete_ranked_split() {
	let row = $(this).parent();
	let season = row.find(".write_ranked_split_season input").eq(0).val();
	let split = row.find(".write_ranked_split_split input").eq(0).val();

	let confirmation = confirm(`Split ${season}-${split} wirklich löschen?`);

	if (!confirmation) {
		return;
	}

	await fetch(`./admin/ajax/db-changes.php`, {
		method: "POST",
		headers: {
			type: "remove_split",
			season: season,
			split: split,
		}
	})
		.then(res => {
			row.remove();
		})
		.catch(e => console.error(e));
}
$(document).ready(function () {
	$("button.open_ranked_split_popup").on("click", ()=> {$(`dialog#ranked-split-popup`)[0].showModal()});
	$("div.ranked-split-edit input").on("change", detect_input_change);
	$('button.add_ranked_split').on('click', add_new_ranked_split);
	$('button.save_ranked_split_changes').on('click', save_ranked_splits);
	$("div.ranked-split-edit .reset_inputs").on("click", reset_inputs);
	$("div.ranked-split-edit .delete_ranked_split").on("click", delete_ranked_split);
});

function detect_input_change() {
	if (this.value !== this.defaultValue) {
		$(this).addClass("input_changed");
	} else {
		$(this).removeClass("input_changed");
	}
}
function reset_inputs() {
	$(this).parent().find("input").each(function(i, obj) {
		obj.value = obj.defaultValue;
		$(obj).removeClass("input_changed");
	})
}