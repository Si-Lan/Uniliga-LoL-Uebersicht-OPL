$(document).ready(() => {
	$("#turnier-button-get").on("click", get_tournament);
	$("#input-tournament-id").on("keydown", (event) => {
		if (event.key === "Enter") get_tournament();
	});
	$("#write_tournament").on("click", () => write_tournament());
	$(".update_tournament").on("click", function () {write_tournament(this.previousSibling.getAttribute("data-id"))});
	$(".get-teams").on("click", function () {get_teams_for_tournament(this.getAttribute("data-id"))});
	$(".get-teams-delete").on("click", function () {get_teams_for_tournament(this.getAttribute("data-id"),true)});
	$(".get-players").on("click", function () {get_players_for_tournament(this.getAttribute("data-id"))});
	$(".get-summoners").on("click", function () {get_summonerNames_for_tournament(this.getAttribute("data-id"))});
	$(".get-matchups").on("click", function () {get_matchups_for_tournament(this.getAttribute("data-id"))});
	$(".get-matchups-delete").on("click", function () {get_matchups_for_tournament(this.getAttribute("data-id"),true)});
	$(".get-results").on("click", function () {get_results_for_tournament(this.getAttribute("data-id"))});
	$(".calculate-standings").on("click", function () {calculate_standings_from_matchups(this.getAttribute("data-id"))});

	$(".open-tournament-data-popup").on("click", function() {$(`dialog.tournament-data-popup.${this.getAttribute("data-id")}`)[0].showModal()});
});

function create_tournament_buttons() {
	let ref_button = document.getElementsByClassName('refresh-tournaments')[0] ?? null;
	if (ref_button != null) {
		ref_button.innerHTML = "Refreshing...";
	}
	fetch(`./admin/ajax/create_admin_buttons.php`, {
		method: "GET",
	})
		.then(res => res.text())
		.then(content => {
			document.getElementsByClassName("turnier-select")[0].innerHTML = content;
			$(".update_tournament").on("click", function () {write_tournament(this.previousSibling.getAttribute("data-id"))});
			$(".open-tournament-data-popup").on("click", function() {$(`dialog.tournament-data-popup.${this.getAttribute("data-id")}`)[0].showModal()});
			$('dialog.dismissable-popup').on('click', function (event) {if (event.target === this) this.close()});
			$(".get-teams").on("click", function () {get_teams_for_tournament(this.getAttribute("data-id"))});
			$(".get-teams-delete").on("click", function () {get_teams_for_tournament(this.getAttribute("data-id"),true)});
			$(".get-players").on("click", function () {get_players_for_tournament(this.getAttribute("data-id"))});
			$(".get-summoners").on("click", function () {get_summonerNames_for_tournament(this.getAttribute("data-id"))});
			$(".get-matchups").on("click", function () {get_matchups_for_tournament(this.getAttribute("data-id"))});
			$(".get-matchups-delete").on("click", function () {get_matchups_for_tournament(this.getAttribute("data-id"),true)});
			$(".get-results").on("click", function () {get_results_for_tournament(this.getAttribute("data-id"))});
			$(".calculate-standings").on("click", function () {calculate_standings_from_matchups(this.getAttribute("data-id"))});
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
			$("dialog#tournament-add .dialog-content .tournament-write-data").remove();
			$("dialog#tournament-add .dialog-content #write_tournament").remove();
			$("dialog#tournament-add .dialog-content").append(result.button);
			$("dialog#tournament-add")[0].showModal();
			$("#write_tournament").on("click", () => write_tournament());
		})
		.catch(e => console.error(e));
}

function write_tournament(tournamentID = null) {
	let id_class = (tournamentID === null) ? "write-popup" : tournamentID;
	let data = {};
	data.OPL_ID = $(`.tournament-write-data.${id_class} label.write_tournament_id input`).val();
	data.OPL_ID_parent = $(`.${id_class} label.write_tournament_parent input`).val();
	data.name = $(`.${id_class} label.write_tournament_name input`).val();
	data.split = $(`.${id_class} label.write_tournament_split select`).find(":selected").val();
	data.season = $(`.${id_class} label.write_tournament_season input`).val();
	data.eventType = $(`.${id_class} label.write_tournament_type select`).find(":selected").val();
	data.format = $(`.${id_class} label.write_tournament_format select`).find(":selected").val();
	data.number = $(`.${id_class} label.write_tournament_number input`).val();
	data.numberRangeTo = $(`.${id_class} label.write_tournament_number2 input`).val();
	data.dateStart = $(`.${id_class} label.write_tournament_startdate input`).val();
	data.dateEnd = $(`.${id_class} label.write_tournament_enddate input`).val();
	data.OPL_logo_url = $(`.${id_class} label.write_tournament_logourl input`).val();
	data.OPL_ID_logo = $(`.${id_class} label.write_tournament_logoid input`).val();
	data.finished = $(`.${id_class} label.write_tournament_finished input`).prop("checked") ? 1 : 0;
	data.deactivated = !$(`.${id_class} label.write_tournament_show input`).prop("checked") ? 1 : 0;
	//console.log(data);
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
						loadingbar_width += 100/teams.length;
						button.attr("style",`--loading-bar-width:${loadingbar_width}%`);
					})
					.catch(e => console.error(e));
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
						loadingbar_width += 100/teams.length;
						button.attr("style",`--loading-bar-width:${loadingbar_width}%`);
					})
					.catch(e => console.error(e));
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
						loadingbar_width += 100/matches.length;
						button.attr("style",`--loading-bar-width:${loadingbar_width}%`);
					})
					.catch(e => console.error(e));
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