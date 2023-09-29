$(document).ready(() => {
	$("#turnier-button-get").on("click", get_tournament);
	$("#input-tournament-id").on("keydown", (event) => {
		if (event.key === "Enter") get_tournament();
	});
	$("#write_tournament").on("click", () => write_tournament());
	$(".update_tournament").on("click", function () {write_tournament(this.previousSibling.getAttribute("data-id"))});
	$(".get-teams").on("click", function () {get_teams_for_tournament(this.getAttribute("data-id"))});
	$(".get-players").on("click", function () {get_players_for_tournament(this.getAttribute("data-id"))});
	$(".get-summoners").on("click", function () {get_summonerNames_for_tournament(this.getAttribute("data-id"))});
	$(".get-matchups").on("click", function () {get_matchups_for_tournament(this.getAttribute("data-id"))});
	$(".get-results").on("click", function () {get_results_for_tournament(this.getAttribute("data-id"))});

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
			$(".get-players").on("click", function () {get_players_for_tournament(this.getAttribute("data-id"))});
			$(".get-summoners").on("click", function () {get_summonerNames_for_tournament(this.getAttribute("data-id"))});
			$(".get-matchups").on("click", function () {get_matchups_for_tournament(this.getAttribute("data-id"))});
			$(".get-results").on("click", function () {get_results_for_tournament(this.getAttribute("data-id"))});
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

function get_teams_for_tournament(tournamentID) {
	fetch(`./admin/ajax/get-opl-data.php`, {
		method: "GET",
		headers: {
			"type": "get_teams_for_tournament",
			"id": tournamentID,
		}
	})
		.then(res => res.json())
		.then(result => {
			console.log(result);
		})
		.catch(e => console.error(e));
}

function get_players_for_tournament(tournamentID) {
	fetch(`./admin/ajax/get-opl-data.php`, {
		method: "GET",
		headers: {
			"type": "get_players_for_tournament",
			"id": tournamentID,
		}
	})
		.then(res => res.json())
		.then(result => {
			console.log(result);
		})
		.catch(e => console.error(e));
}

function get_summonerNames_for_tournament(tournamentID) {
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
						"id": team.OPL_ID,
					}
				})
					.then(res => res.json())
					.then(result => {
						console.log(result);
					})
					.catch(e => console.error(e));
				// no need to wait here, fetched php script sleeps for 1 sec at the end
			}
		})
		.catch(e => console.error(e));
}

function get_matchups_for_tournament(tournamentID) {
	fetch(`./admin/ajax/get-opl-data.php`, {
		method: "GET",
		headers: {
			"type": "get_matchups_for_tournament",
			"id": tournamentID,
		}
	})
		.then(res => res.json())
		.then(result => {
			console.log(result);
		})
		.catch(e => console.error(e));
}

function get_results_for_tournament(tournamentID) {
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
				await new Promise(r => setTimeout(r, 1000));
			}
		})
		.catch(e => console.error(e));
}