function write_result(wrapper, container, input) {
	wrapper.removeClass('no-res');
	let scroll_on_write = container.scrollTop() + wrapper.height() > container.prop("scrollHeight") - 50;
	container.append(input);
	if (scroll_on_write) container.scrollTop(container.prop("scrollHeight"));
}

function clear_results(ID,num=0) {
	let results = $(".result-wrapper."+ID);
	if (num>0) {
		results = results.eq(num-1);
	}
	if (!results.hasClass("no-res")) {
		results.addClass("no-res");
		results.find(".result-content").html("");
	}
}
function get_puuids(tournID, only_without_puuid = true) {
	console.log("----- Start getting PUUIDs -----");
	let currButton;
	if (only_without_puuid) {
		currButton = $("button.write.puuids."+tournID);
	} else {
		currButton = $("button.write.puuids-all."+tournID);
	}
	if (!currButton.hasClass("loading-data")) {
		$("button.write."+tournID).addClass("loading-data");
		currButton.append("<div class='lds-dual-ring'></div>");
		set_all_buttons_onclick(0,tournID);
	}

	let teams_request = new XMLHttpRequest();
	teams_request.onreadystatechange = async function () {
		if (this.readyState === 4 && this.status === 200) {
			let teams = JSON.parse(this.responseText);
			console.log(teams.length + " Teams got:");
			console.log(teams);
			let loops_done = 0;
			let max_loops = teams.length;
			let container = $(".result-wrapper."+tournID+" .result-content");
			container.append("----- "+teams.length+" Teams gefunden -----<br>");
			let rgapi_calls_made = 0;

			for (let i=0; i < teams.length; i++) {
				console.log("Starting with Team "+(i+1));
				let calling = parseInt(teams[i]["player_count"]);
				rgapi_calls_made += calling;
				console.log("-- called "+(rgapi_calls_made-calling));
				container.append("-- called "+(rgapi_calls_made-calling)+"<br>");
				if (rgapi_calls_made >= 50) {
					console.log("-- "+(rgapi_calls_made-calling)+" calls made");
					container.append("-- "+(rgapi_calls_made-calling)+" calls being made<br>");
					console.log("-- sleep (#"+ (i+1) +") --");
					for (let t = 0; t <= 10; t++) {
						await new Promise(r => setTimeout(r, 1000));
						container.append("----- wait "+ (10-t) +" -----<br>");
						container.scrollTop(container.prop("scrollHeight"));
					}
					console.log("-- slept --");
					rgapi_calls_made = calling;
				}

				let puuid_request = new XMLHttpRequest();
				puuid_request.onreadystatechange = function () {
					if (this.readyState === 4 && this.status === 200) {
						loops_done++;
						console.log("Team "+(i+1)+" ready");
						$('.result-wrapper.'+tournID).removeClass('no-res');
						let result = JSON.parse(this.responseText);
						console.log(result["echo"]);
						container.append("#"+(i+1)+"<br>");
						container.append(result["echo"]);
						container.scrollTop(container.prop("scrollHeight"));
						if (loops_done >= max_loops) {
							console.log("----- Done with getting PUUIDs -----");
							container.append("<br>----- Done with getting PUUIDs -----<br>");
							container.scrollTop(container.prop("scrollHeight"));
							$("button.write."+tournID).removeClass('loading-data');
							currButton.children(".lds-dual-ring").remove();
							set_all_buttons_onclick(1,tournID);
						}
					}
				};
				if (only_without_puuid) {
					puuid_request.open("GET", "./admin/ajax/get-rgapi-data.php?type=puuids-by-team&team="+teams[i]['OPL_ID'], true);
				} else {
					puuid_request.open("GET", "./admin/ajax/get-rgapi-data.php?type=puuids-by-team&team="+teams[i]['OPL_ID']+"&all", true);
				}
				puuid_request.send();
			}

			if (teams.length === 0) {
				$('.result-wrapper.'+tournID).removeClass('no-res');
				console.log("----- Done with getting PUUIDs -----");
				container.append("----- Done with getting PUUIDs -----<br>");
				$("button.write."+tournID).removeClass("loading-data");
				currButton.children(".lds-dual-ring").remove();
				set_all_buttons_onclick(1,tournID);
			}
		}
	};
	if (only_without_puuid) {
		teams_request.open("GET", "./ajax/get-data.php", true);
		teams_request.setRequestHeader("type", "teams");
		teams_request.setRequestHeader("tournamentID", tournID);
		teams_request.setRequestHeader("playercount", "true");
		teams_request.setRequestHeader("nopuuid", "true");
	} else {
		teams_request.open("GET", "./ajax/get-data.php", true);
		teams_request.setRequestHeader("type", "teams");
		teams_request.setRequestHeader("tournamentID", tournID);
		teams_request.setRequestHeader("playercount", "true");
	}
	teams_request.send();
}

async function get_riotids_by_puuids(tournamentID) {
	console.log("----- Start getting RiotIDs -----");
	let currButton = $(`button.write.riotids-puuids.${tournamentID}`);
	let allButtons = $(`button.write.${tournamentID}`);
	let container = $(`.result-wrapper.${tournamentID} .result-content`);
	let wrapper = $(`.result-wrapper.${tournamentID}`);
	let date = Date.now();

	if (!currButton.hasClass("loading-data")) {
		allButtons.addClass("loading-data");
		currButton.append("<div class='lds-dual-ring'></div>");
		set_all_buttons_onclick(0,tournamentID);
	}

	await fetch(`./ajax/get-data.php`, {
		method: "GET",
		headers: {
			"type": "players",
			"tournamentid": tournamentID,
			"puuidset": "true",
		}
	})
		.then(res => res.json())
		.then(async player_data => {
			container.append("----- "+player_data.length+" Spieler gefunden -----<br>");
			wrapper.removeClass('no-res');
			let limit = 50;
			let fetches = [];
			for (let i = 0; i < player_data.length; i++) {
				console.log("Starting with Player " + (i + 1));
				fetches.push(
					fetch(`./admin/ajax/get-rgapi-data.php`, {
						method: "GET",
						headers: {
							"type": "riotid_for_player",
							"player": player_data[i]["OPL_ID"]
						}
					})
						.then(res => res.json())
						.then(result => {
							console.log("Player " + (i + 1) + " ready");
							console.log(result["echo"]);
							write_result(wrapper, container, `#${i + 1}<br>${result["echo"]}`);
							if ((Date.now()-date)/1000 > 10) {
								date = Date.now();
							}
						})
						.catch(error => console.error(error))
				);

				if (i % (limit-1) === 0 && i !== 0) {
					date = Date.now();
					await Promise.all(fetches);
					fetches = [];
					let time_passed = Math.ceil((Date.now()-date)/1000);
					if (time_passed < 10) {
						console.log("-- sleep (before #"+ (i+1) +") --");
						for (let t = 0; t <= 10-time_passed; t++) {
							write_result(wrapper,container, `----- waiting ${10-time_passed-t+1} -----<br>`);
							await new Promise(r => setTimeout(r, 1000));
						}
						write_result(wrapper,container, `----- starting next batch (${i+1}-${i+limit}) -----<br>`);
						console.log("-- slept --");
					}
				}
			}
			if (fetches.length !== 0) {
				await Promise.all(fetches);
				fetches = [];
			}
		})
		.catch(error => console.error(error));

	allButtons.removeClass("loading-data");
	currButton.children(".lds-dual-ring").remove();
	set_all_buttons_onclick(1,tournamentID);
	wrapper.removeClass('no-res');
	console.log("----- Done with getting RiotIDs -----");
	write_result(wrapper,container,"----- Done with getting RiotIDs -----<br>");
}

function get_games_for_team(tournID,teamID) {
	let cont = confirm("Funktion ist für neue Turniere (ab Winter 24/25) nicht mehr unterstützt. Wirklich fortfahren?");
	if (!cont) return;
	cont = confirm("Funktion sollte wirklich nicht mehr benutzt werden. Fortfahren?");
	if (!cont) return;

	console.log("----- Start getting Games (by Team) -----");
	let currButton = $("button.write.games-team."+ tournID);
	if (!currButton.hasClass("loading-data")) {
		$("button.write."+tournID).addClass("loading-data");
		currButton.append("<div class='lds-dual-ring'></div>");
		set_all_buttons_onclick(0,tournID,teamID);
	}

	let players_request = new XMLHttpRequest();
	players_request.onreadystatechange = async function () {
		if (this.readyState === 4 && this.status === 200) {
			let players = JSON.parse(this.responseText);
			console.log(players.length+" Players got");
			console.log(players);
			let loops_done = 0;
			let max_loops = players.length;
			let container = $(".result-wrapper."+tournID+" .result-content");
			container.append("----- "+players.length+" Spieler gefunden -----<br>");

			for (let i=0; i < players.length; i++) {
				console.log("Starting with Player "+(i+1));
				let games_request = new XMLHttpRequest();
				games_request.onreadystatechange = function () {
					if (this.readyState === 4 && this.status === 200) {
						loops_done++;
						console.log("Player "+(i+1)+" ready");
						$(".result-wrapper."+tournID).removeClass("no-res");
						let result = this.responseText;
						console.log(result);
						container.append("#"+(i+1)+"<br>");
						container.append(result);
						container.scrollTop(container.prop("scrollHeight"));
						if (loops_done >= max_loops) {
							console.log("----- Done with getting Games (by Team) -----");
							container.append("<br>----- Done with getting Games (by Team) -----<br>");
							container.scrollTop(container.prop("scrollHeight"));
							$("button.write."+tournID).removeClass('loading-data');
							currButton.children(".lds-dual-ring").remove();
							set_all_buttons_onclick(1,tournID,teamID);
						}
					}
				};
				if ((i+1) % 50 === 0) {
					console.log("-- sleep (#"+ (i+1) +") --");
					for (let t = 0; t <= 10; t++) {
						await new Promise(r => setTimeout(r, 1000));
						container.append("----- wait "+ (10-t) +" -----<br>");
						container.scrollTop(container.prop("scrollHeight"));
					}
					console.log("-- slept --");
				}
				games_request.open("GET",`./admin/ajax/get-rgapi-data.php?type=games-by-player&player=${players[i]["OPL_ID"]}&tournament=${tournID}`, true);
				games_request.send();
			}

			if (players.length === 0) {
				$('.result-wrapper.'+tournID).removeClass('no-res');
				console.log("----- Done with getting Games (by Team) -----");
				container.append("----- Done with getting Games (by Team) -----<br>");
				container.scrollTop(container.prop("scrollHeight"));
				$("button.write."+tournID).removeClass("loading-data");
				currButton.children(".lds-dual-ring").remove();
				set_all_buttons_onclick(1,tournID,teamID);
			}
		}
	};
	players_request.open("GET","./ajax/get-data.php", true);
	players_request.setRequestHeader("type", "players");
	players_request.setRequestHeader("teamID", teamID);
	players_request.setRequestHeader("puuidset", "true");
	players_request.send();
}

function get_games_for_division(tournID,divID) {
	let cont = confirm("Funktion ist für neue Turniere (ab Winter 24/25) nicht mehr unterstützt. Wirklich fortfahren?");
	if (!cont) return;
	cont = confirm("Funktion sollte wirklich nicht mehr benutzt werden. Fortfahren?");
	if (!cont) return;

	console.log("----- Start getting Games (by Division) -----");
	let currButton = $("button.write.games-div."+divID);
	if (!currButton.hasClass("loading-data")) {
		$("button.write."+tournID).addClass("loading-data");
		currButton.append("<div class='lds-dual-ring'></div>");
		currButton.attr("onClick","");
	}

	let teams_request = new XMLHttpRequest();
	teams_request.onreadystatechange = async function () {
		if (this.readyState === 4 && this.status === 200) {
			let teams = JSON.parse(this.responseText);
			console.log(teams.length+" Teams got");
			console.log(teams);
			let t_loops_done = 0;
			let t_max_loops = teams.length;
			let container = $(".result-wrapper."+divID+" .result-content");
			container.append("----- "+teams.length+" Teams gefunden -----<br>");
			container.scrollTop(container.prop("scrollHeight"));
			$(".result-wrapper."+divID).removeClass("no-res");

			for (let t=0; t < teams.length; t++) {
				let players_request = new XMLHttpRequest();
				players_request.onreadystatechange = async function () {
					if (this.readyState === 4 && this.status === 200) {
						console.log("Start Team "+(t+1));
						let players = JSON.parse(this.responseText);
						console.log(players.length+" Players got");
						console.log(players);
						container.append("Team "+(t+1)+":<br>");
						container.append(players.length+" Spieler gefunden<br>");
						container.scrollTop(container.prop("scrollHeight"));
						let plcons = players.length;
						if (players.length >= 5) {
							for(let l = plcons-1; l>=plcons-4; l--){
								players.splice(Math.floor(Math.random()*players.length), 1);
							}
						}
						let p_loops_done = 0;
						let p_max_loops = players.length;
						container.append(players.length+" Spieler werden durchsucht<br><br>");
						container.scrollTop(container.prop("scrollHeight"));
						$(".result-wrapper."+divID).removeClass("no-res");

						for (let p=0; p < players.length; p++) {
							console.log("Starting with Player " + (p + 1));
							let games_request = new XMLHttpRequest();
							games_request.onreadystatechange = function () {
								if (this.readyState === 4 && this.status === 200) {
									if(p === players.length-1) {
										t_loops_done++;
									}
									p_loops_done++;
									console.log(`Player ${(p+1)} ready`);
									let result = this.responseText;
									console.log(result);
									container.append(`Team ${(t+1)} Spieler ${(p+1)}:<br>`);
									container.append(result);
									container.scrollTop(container.prop("scrollHeight"));
									$(".result-wrapper."+divID).removeClass("no-res");
									if (t_loops_done >= t_max_loops && p_loops_done >= p_max_loops) {
										console.log("----- Done with getting Games (by Division) -----");
										container.append("<br>----- Done with getting Games (by Division) -----<br>");
										container.scrollTop(container.prop("scrollHeight"));
										$("button.write."+tournID).removeClass('loading-data');
										currButton.children(".lds-dual-ring").remove();
										currButton.attr("onClick",`get_games_for_division("${tournID}","${divID}")`);
									}
								}
							};
							games_request.open("GET",`./admin/ajax/get-rgapi-data.php?type=games-by-player&player=${players[p]["OPL_ID"]}&tournament=${tournID}`, true);
							games_request.send();
						}
					}
				};
				if ((t+1) % 10 === 0) {
					console.log("-- sleep (#"+ (t+1) +") --");
					for (let time = 0; time <= 10; time++) {
						await new Promise(r => setTimeout(r, 1000));
						container.append("----- wait "+ (10-time) +" -----<br>");
						container.scrollTop(container.prop("scrollHeight"));
					}
					console.log("-- slept --");
				}
				players_request.open("GET","./ajax/get-data.php", true);
				players_request.setRequestHeader("type", "players");
				players_request.setRequestHeader("teamID", teams[t]['OPL_ID']);
				players_request.setRequestHeader("puuidset", "true");
				players_request.send();
			}
		}
	};
	teams_request.open("GET","./ajax/get-data.php", true);
	teams_request.setRequestHeader("type", "teams");
	teams_request.setRequestHeader("tournamentID", divID);
	teams_request.send();
}

function get_games_for_group(tournID, groupID) {
	let cont = confirm("Funktion ist für neue Turniere (ab Winter 24/25) nicht mehr unterstützt. Wirklich fortfahren?");
	if (!cont) return;
	cont = confirm("Funktion sollte wirklich nicht mehr benutzt werden. Fortfahren?");
	if (!cont) return;

	console.log("----- Start getting Games (by Group) -----");
	let currButton = $("button.write.games-group."+groupID);
	if (!currButton.hasClass("loading-data")) {
		$("button.write."+tournID).addClass("loading-data");
		currButton.append("<div class='lds-dual-ring'></div>");
		currButton.attr("onClick","");
	}

	let teams_request = new XMLHttpRequest();
	teams_request.onreadystatechange = async function () {
		if (this.readyState === 4 && this.status === 200) {
			let teams = JSON.parse(this.responseText);
			console.log(teams.length+" Teams got");
			console.log(teams);
			let t_loops_done = 0;
			let t_max_loops = teams.length;
			let container = $(".result-wrapper."+groupID+" .result-content");
			container.append("----- "+teams.length+" Teams gefunden -----<br>");
			container.scrollTop(container.prop("scrollHeight"));
			$(".result-wrapper."+groupID).removeClass("no-res");

			for (let t=0; t < teams.length; t++) {
				let players_request = new XMLHttpRequest();
				players_request.onreadystatechange = async function () {
					if (this.readyState === 4 && this.status === 200) {
						console.log("Start Team "+(t+1));
						let players = JSON.parse(this.responseText);
						console.log(players.length+" Players got");
						console.log(players);
						container.append("Team "+(t+1)+":<br>");
						container.append(players.length+" Spieler gefunden<br>");
						container.scrollTop(container.prop("scrollHeight"));
						let plcons = players.length;
						if (players.length >= 5) {
							for(let l = plcons-1; l>=plcons-4; l--){
								players.splice(Math.floor(Math.random()*players.length), 1);
							}
						}
						let p_loops_done = 0;
						let p_max_loops = players.length;
						container.append(players.length+" Spieler werden durchsucht<br><br>");
						container.scrollTop(container.prop("scrollHeight"));
						$(".result-wrapper."+groupID).removeClass("no-res");

						for (let p=0; p < players.length; p++) {
							console.log("Starting with Player " + (p + 1));
							let games_request = new XMLHttpRequest();
							games_request.onreadystatechange = function () {
								if (this.readyState === 4 && this.status === 200) {
									if(p === players.length-1) {
										t_loops_done++;
									}
									p_loops_done++;
									console.log("Player "+(p+1)+" ready");
									let result = this.responseText;
									console.log(result);
									container.append("Team "+(t+1)+" Spieler "+(p+1)+":<br>");
									container.append(result);
									container.scrollTop(container.prop("scrollHeight"));
									$(".result-wrapper."+groupID).removeClass("no-res");
									if (t_loops_done >= t_max_loops && p_loops_done >= p_max_loops) {
										console.log("----- Done with getting Games (by Division) -----");
										container.append("<br>----- Done with getting Games (by Division) -----<br>");
										container.scrollTop(container.prop("scrollHeight"));
										$("button.write."+tournID).removeClass('loading-data');
										currButton.children(".lds-dual-ring").remove();
										currButton.attr("onClick",`get_games_for_group("${tournID}","${groupID}")`);
									}
								}
							};
							games_request.open("GET",`./admin/ajax/get-rgapi-data.php?type=games-by-player&player=${players[p]["OPL_ID"]}&tournament=${tournID}`, true);
							games_request.send();
						}
					}
				};
				if ((t+1) % 10 === 0) {
					console.log("-- sleep (#"+ (t+1) +") --");
					for (let time = 0; time <= 10; time++) {
						await new Promise(r => setTimeout(r, 1000));
						container.append("----- wait "+ (10-time) +" -----<br>");
						container.scrollTop(container.prop("scrollHeight"));
					}
					console.log("-- slept --");
				}
				players_request.open("GET","./ajax/get-data.php", true);
				players_request.setRequestHeader("type", "players");
				players_request.setRequestHeader("teamID", teams[t]['OPL_ID']);
				players_request.setRequestHeader("puuidset", "true");
				players_request.send();
			}
		}
	};
	teams_request.open("GET","./ajax/get-data.php", true);
	teams_request.setRequestHeader("type", "teams");
	teams_request.setRequestHeader("tournamentID", groupID);
	teams_request.send();
}

function get_game_data(tournamentID, teamID = 0, all = 0) {
	console.log("----- Start getting Gamedata -----");
	let currButton;
	if (all === 0) {
		currButton = $("button.write.gamedata."+ tournamentID);
	} else {
		currButton = $("button.write.gamedata-all."+ tournamentID);
	}
	if (!currButton.hasClass("loading-data")) {
		$("button.write."+tournamentID).addClass("loading-data");
		currButton.append("<div class='lds-dual-ring'></div>");
		set_all_buttons_onclick(0,tournamentID,teamID);
	}

	let games_request = new XMLHttpRequest();
	games_request.onreadystatechange = async function () {
		if (this.readyState === 4 && this.status === 200) {
			let games = JSON.parse(this.responseText);
			console.log(games.length+" Games found");
			console.log(games);
			let loops_done = 0;
			let max_loops = games.length;
			let container = currButton.siblings(".result-wrapper."+tournamentID).children(".result-content");
			container.append("----- "+games.length+" Spiele gefunden -----<br>");
			container.scrollTop(container.prop("scrollHeight"));

			for (let i = 0; i < games.length; i++) {
				console.log("Starting with Game "+(i+1));
				let data_request = new XMLHttpRequest();
				data_request.onreadystatechange = function () {
					if (this.readyState === 4 && this.status === 200) {
						loops_done++;
						console.log("Game "+(i+1)+" ready");
						currButton.siblings(".result-wrapper."+tournamentID).removeClass("no-res");
						let result = this.responseText;
						console.log(result);
						container.append("#"+(i+1)+"<br>");
						container.append(result);
						container.scrollTop(container.prop("scrollHeight"));
						container.scrollTop(container.prop("scrollHeight"));
						if (loops_done >= max_loops) {
							console.log("----- Done with getting Game-Data -----");
							container.append("----- Done with getting Game-Data -----<br>");
							container.scrollTop(container.prop("scrollHeight"));
							$("button.write."+tournamentID).removeClass("loading-data");
							currButton.children(".lds-dual-ring").remove();
							set_all_buttons_onclick(1,tournamentID,teamID);
						}
					}
				};
				if ((i+1) % 50 === 0) {
					console.log("-- sleep (#"+ (i+1) +") --");
					for (let time = 0; time <= 10; time++) {
						await new Promise(r => setTimeout(r, 1000));
						container.append("----- wait "+ (10-time) +" -----<br>");
						container.scrollTop(container.prop("scrollHeight"));
					}
					console.log("-- slept --");
				}
				data_request.open("GET",`./admin/ajax/get-rgapi-data.php?type=add-match-data&match=${games[i]['RIOT_matchID']}&tournament=${tournamentID}`, true);
				data_request.send();
			}

			if (games.length === 0) {
				currButton.siblings('.result-wrapper.'+tournamentID).removeClass('no-res');
				console.log("----- Done with getting Game-Data -----");
				container.append("----- Done with getting Game-Data -----<br>");
				container.scrollTop(container.prop("scrollHeight"));
				$("button.write."+tournamentID).removeClass("loading-data");
				currButton.children(".lds-dual-ring").remove();
				set_all_buttons_onclick(1,tournamentID,teamID);
			}
		}
	};

	games_request.open("GET","./ajax/get-data.php",true);
	games_request.setRequestHeader("type", "all-games");
	games_request.setRequestHeader("no-data-only", "true");
	games_request.send();
}

function assign_and_filter_games(tournamentID,teamID = 0, all = 0) {
	let cont = confirm("Funktion ist für neue Turniere (ab Winter 24/25) nicht mehr unterstützt. Wirklich fortfahren?");
	if (!cont) return;
	cont = confirm("Funktion sollte wirklich nicht mehr benutzt werden. Fortfahren?");
	if (!cont) return;

	console.log("----- Start sorting Games -----");
	let currButton;
	if (all === 0) {
		currButton = $("button.write.assign-una."+tournamentID);
	} else {
		currButton = $("button.write.assign-all."+tournamentID);
	}
	if (!currButton.hasClass("loading-data")) {
		$("button.write."+tournamentID).addClass("loading-data");
		currButton.append("<div class='lds-dual-ring'></div>");
		set_all_buttons_onclick(0,tournamentID,teamID);
	}

	let games_request = new XMLHttpRequest();
	games_request.onreadystatechange = async function () {
		if (this.readyState === 4 && this.status === 200) {
			let games = JSON.parse(this.responseText);
			console.log(games.length+" Games found");
			console.log(games);
			let loops_done = 0;
			let max_loops = games.length;
			let container = currButton.siblings(".result-wrapper."+tournamentID).children(".result-content");
			container.append("----- "+games.length+" Spiele gefunden -----<br>");
			container.scrollTop(container.prop("scrollHeight"));

			for (let i = 0; i < games.length; i++) {
				console.log("Starting with Game "+(i+1));
				let sort_request = new XMLHttpRequest();
				sort_request.onreadystatechange = function () {
					if (this.readyState === 4 && this.status === 200) {
						loops_done++;
						console.log("Game "+(i+1)+" ready");
						currButton.siblings(".result-wrapper."+tournamentID).removeClass("no-res");
						let result = this.responseText;
						console.log(result);
						container.append("#"+(i+1)+"<br>");
						container.append(result);
						container.scrollTop(container.prop("scrollHeight"));
						container.scrollTop(container.prop("scrollHeight"));
						if (loops_done >= max_loops) {
							console.log("----- Done with sorting Games -----");
							container.append("----- Done with sorting Games -----<br>");
							container.scrollTop(container.prop("scrollHeight"));
							$("button.write."+tournamentID).removeClass("loading-data");
							currButton.children(".lds-dual-ring").remove();
							set_all_buttons_onclick(1,tournamentID,teamID);
						}
					}
				};
				sort_request.open("GET","./admin/ajax/get-rgapi-data.php?type=assign-and-filter&match="+games[i]['RIOT_matchID']+"&tournament="+tournamentID,true);
				sort_request.send();
				await new Promise(r => setTimeout(r, 100));
			}

			if (games.length === 0) {
				currButton.siblings('.result-wrapper.'+tournamentID).removeClass('no-res');
				console.log("----- Done with sorting Games -----");
				container.append("----- Done with sorting Games -----<br>");
				container.scrollTop(container.prop("scrollHeight"));
				$("button.write."+tournamentID).removeClass("loading-data");
				currButton.children(".lds-dual-ring").remove();
				set_all_buttons_onclick(1,tournamentID,teamID);
			}
		}
	};
	if (all === 0) {
		games_request.open("GET","./ajax/get-data.php",true);
		games_request.setRequestHeader("type", "games-in-tournaments-time");
		games_request.setRequestHeader("tournamentID", tournamentID);
		games_request.setRequestHeader("unassigned-only", "true");
	} else {
		games_request.open("GET","./ajax/get-data.php",true);
		games_request.setRequestHeader("type", "games-in-tournaments-time");
		games_request.setRequestHeader("tournamentID", tournamentID);
	}
	games_request.send();
}

function get_ranks(tournamentID) {
	console.log("----- Start getting Ranks -----");
	let currButton  = $("button.write.get-ranks."+tournamentID);
	if (!currButton.hasClass("loading-data")) {
		$("button.write."+tournamentID).addClass("loading-data");
		currButton.append("<div class='lds-dual-ring'></div>");
		set_all_buttons_onclick(0,tournamentID);
	}

	let players_request = new XMLHttpRequest();
	players_request.onreadystatechange = async function () {
		if (this.readyState === 4 && this.status === 200) {
			let players = JSON.parse(this.responseText);
			console.log(players.length+" Players got");
			console.log(players);
			let loops_done = 0;
			let max_loops = players.length;
			let container = $(".result-wrapper."+tournamentID+" .result-content");
			container.append("----- "+players.length+" Spieler gefunden -----<br>");

			for (let i=0; i < players.length; i++) {
				console.log("Starting with Player "+(i+1));
				let rank_request = new XMLHttpRequest();
				rank_request.onreadystatechange = function () {
					if (this.readyState === 4 && this.status === 200) {
						loops_done++;
						console.log("Player "+(i+1)+" ready");
						$('.result-wrapper.'+tournamentID).removeClass('no-res');
						let result = this.responseText;
						console.log(result);
						container.append("#"+(i+1)+"<br>");
						container.append(result);
						container.scrollTop(container.prop("scrollHeight"));
						if (loops_done >= max_loops) {
							console.log("----- Done with getting Ranks -----");
							container.append("<br>----- Done with getting Ranks -----<br>");
							container.scrollTop(container.prop("scrollHeight"));
							$("button.write."+tournamentID).removeClass('loading-data');
							currButton.children(".lds-dual-ring").remove();
							set_all_buttons_onclick(1,tournamentID);
						}
					}
				};
				if ((i+1) % 50 === 0) {
					console.log("-- sleep (#"+ (i+1) +") --");
					for (let t = 0; t <= 10; t++) {
						await new Promise(r => setTimeout(r, 1000));
						container.append("----- wait "+ (10-t) +" -----<br>");
						container.scrollTop(container.prop("scrollHeight"));
					}
					console.log("-- slept --");
				}
				rank_request.open("GET","./admin/ajax/get-rgapi-data.php?type=get-rank-for-player&player="+players[i]["OPL_ID"], true);
				rank_request.send();
			}

			if (players.length === 0) {
				$('.result-wrapper.'+tournamentID).removeClass('no-res');
				console.log("----- Done with getting Ranks -----");
				container.append("----- Done with getting Ranks -----<br>");
				container.scrollTop(container.prop("scrollHeight"));
				$("button.write."+tournamentID).removeClass("loading-data");
				currButton.children(".lds-dual-ring").remove();
				set_all_buttons_onclick(1,tournamentID);
			}
		}
	};
	players_request.open("GET","./ajax/get-data.php", true);
	players_request.setRequestHeader("type", "players");
	players_request.setRequestHeader("tournamentID", tournamentID);
	players_request.setRequestHeader("summonerIDset", "true");
	players_request.send();
}


function set_all_buttons_onclick(set, tournamentID, teamID = 0) {
	if (set === 1) {
		$("button.write.puuids."+tournamentID).attr("onClick","get_puuids('"+tournamentID+"')");
		$("button.write.puuids-all."+tournamentID).attr("onClick","get_puuids('"+tournamentID+"',false)");
		$("button.write.riotids-puuids."+tournamentID).attr("onClick","get_riotids_by_puuids('"+tournamentID+"')");
		$("button.write.games-team."+teamID).attr("onClick","get_games_for_team('"+tournamentID+"','"+teamID+"')");
		$("button.write.gamedata."+tournamentID).attr("onClick","get_game_data('"+tournamentID+"','"+teamID+"')");
		$("button.write.assign-una."+tournamentID).attr("onClick","assign_and_filter_games('"+tournamentID+"','"+teamID+"')");
		$("button.write.assign-all."+tournamentID).attr("onClick","assign_and_filter_games('"+tournamentID+"','"+teamID+"',1)");
		$("button.write.get-ranks."+tournamentID).attr("onClick", "get_ranks('"+tournamentID+"')");
		$("button.write.calc-team-rank."+tournamentID).attr("onClick", "get_average_team_ranks('"+tournamentID+"')");
		$("button.write.get-pstats."+tournamentID).attr("onClick", "get_stats_for_players('"+tournamentID+"')");
		$("button.write.teamstats."+tournamentID).attr("onClick", "get_teamstats('"+tournamentID+"')");
	} else if (set === 0) {
		$("button.write."+tournamentID).attr("onClick","");
	}
}

function average_team_rank(team_id) {
	let req = new XMLHttpRequest();
	req.onreadystatechange = function () {
		if (this.readyState === 4 && this.status === 200) {
			return this.responseText;
		}
	};
	req.open("GET","./admin/ajax/get-rgapi-data.php?type=calculate-write-avg-rank&team="+team_id);
	req.send();
}

function get_average_team_ranks(tournament_id) {
	console.log("----- Start calculating avg. Ranks -----");
	let currButton  = $("button.write.calc-team-rank."+tournament_id);
	if (!currButton.hasClass("loading-data")) {
		$("button.write."+tournament_id).addClass("loading-data");
		currButton.append("<div class='lds-dual-ring'></div>");
		set_all_buttons_onclick(0,tournament_id);
	}

	let teams_request = new XMLHttpRequest();
	teams_request.onreadystatechange = function () {
		if (this.readyState === 4 && this.status === 200) {
			let teams = JSON.parse(this.responseText);
			let loops_done = 0;
			let max_loops = teams.length;
			let container = $(".result-wrapper."+tournament_id+" .result-content");
			container.append("----- avg Ranks for "+teams.length+" Teams -----<br>");
			for (const team of teams) {
				let req = new XMLHttpRequest();
				req.onreadystatechange = function () {
					if (this.readyState === 4 && this.status === 200) {
						loops_done++;
						$('.result-wrapper.'+tournament_id).removeClass('no-res');
						let result = this.responseText;
						if (result === "") {
							result = "kein Rang"
						}
						console.log(team['name'] + ": " + result);
						container.append(team['name']+":<br>"+result+"<br>");
						container.scrollTop(container.prop("scrollHeight"));
						if (loops_done >= max_loops) {
							console.log("----- Done with calculating avg. Ranks -----");
							container.append("<br>----- Done with calculating avg. Ranks -----<br>");
							container.scrollTop(container.prop("scrollHeight"));
							$("button.write."+tournament_id).removeClass('loading-data');
							currButton.children(".lds-dual-ring").remove();
							set_all_buttons_onclick(1,tournament_id);
						}
					}
				};
				req.open("GET", `./admin/ajax/get-rgapi-data.php?type=calculate-write-avg-rank&team=${team['OPL_ID']}&tournament=${tournament_id}`);
				req.send();
			}
			if (teams.length === 0) {
				$('.result-wrapper.'+tournament_id).removeClass('no-res');
				console.log("----- Done with calculating avg. Ranks -----");
				container.append("----- Done with calculating avg. Ranks -----<br>");
				container.scrollTop(container.prop("scrollHeight"));
				$("button.write."+tournament_id).removeClass("loading-data");
				currButton.children(".lds-dual-ring").remove();
				set_all_buttons_onclick(1,tournament_id);
			}
		}
	};
	teams_request.open("GET", "./ajax/get-data.php", true);
	teams_request.setRequestHeader("type", "teams");
	teams_request.setRequestHeader("tournamentID", tournament_id);
	teams_request.send();
}

async function get_stats_for_players(tournament_id) {
	console.log("----- Start getting Player-Stats -----");
	let currButton  = $("button.write.get-pstats."+tournament_id);
	let container = $(`.result-wrapper.${tournament_id} .result-content`);
	let wrapper = $(`.result-wrapper.${tournament_id}`);
	let allButtons = $(`button.write.${tournament_id}`);

	if (!currButton.hasClass("loading-data")) {
		$("button.write."+tournament_id).addClass("loading-data");
		currButton.append("<div class='lds-dual-ring'></div>");
		set_all_buttons_onclick(0,tournament_id);
	}

	await fetch(`./ajax/get-data.php`, {
		method: "GET",
		headers: {
			"type": "teams",
			"tournamentID": tournament_id,
		}
	})
		.then(res => res.json())
		.then(async teams => {
			container.append(`----- Stats for Players of ${teams.length} Teams ----- <br>`);
			for (const team of teams) {
				await fetch(`./admin/ajax/get-rgapi-data.php`, {
					method: "GET",
					headers: {
						"type": "get-stats-for-players",
						"team": team.OPL_ID,
						"tournament": tournament_id,
					}
				})
					.then(res => res.text())
					.then(result => {
						wrapper.removeClass('no-res');
						write_result(wrapper,container,`${team.name}:<br> ${result}<br>`);
						console.log(`${team.name}: ${result}`);
					})
					.catch(e => console.error(e));
			}
		})
		.catch(e => console.error(e));

	wrapper.removeClass('no-res');
	write_result(wrapper,container,`----- Done with getting Player-Stats -----<br>`);
	console.log("----- Done with getting Player-Stats -----");

	allButtons.removeClass("loading-data");
	currButton.children(".lds-dual-ring").remove();
	set_all_buttons_onclick(1,tournament_id);

}

function get_teamstats(tournament_id) {
	console.log("----- Start getting played Champions -----");
	let currButton  = $("button.write.teamstats."+tournament_id);
	if (!currButton.hasClass("loading-data")) {
		$("button.write."+tournament_id).addClass("loading-data");
		currButton.append("<div class='lds-dual-ring'></div>");
		set_all_buttons_onclick(0,tournament_id);
	}

	let teams_request = new XMLHttpRequest();
	teams_request.onreadystatechange = function () {
		if (this.readyState === 4 && this.status === 200) {
			let teams = JSON.parse(this.responseText);
			let loops_done = 0;
			let max_loops = teams.length;
			let container = $(".result-wrapper."+tournament_id+" .result-content");
			container.append("----- Teamstats for "+teams.length+" Teams -----<br>");
			for (const team of teams) {
				let req = new XMLHttpRequest();
				req.onreadystatechange = function () {
					if (this.readyState === 4 && this.status === 200) {
						loops_done++;
						$('.result-wrapper.'+tournament_id).removeClass('no-res');
						let result = this.responseText;
						console.log(team['name'] + ": " + result);
						container.append(team['name']+":<br>"+result+"<br>");
						container.scrollTop(container.prop("scrollHeight"));
						if (loops_done >= max_loops) {
							console.log("----- Done with calculating Teamstats -----");
							container.append("<br>----- Done with calculating Teamstats -----<br>");
							container.scrollTop(container.prop("scrollHeight"));
							$("button.write."+tournament_id).removeClass('loading-data');
							currButton.children(".lds-dual-ring").remove();
							set_all_buttons_onclick(1,tournament_id);
						}
					}
				};
				req.open("GET", `./admin/ajax/get-rgapi-data.php?type=calculate-teamstats&team=${team['OPL_ID']}&tournament=${tournament_id}`);
				req.send();
			}
			if (teams.length === 0) {
				$('.result-wrapper.'+tournament_id).removeClass('no-res');
				console.log("----- Done with calculating Teamstats -----");
				container.append("----- Done with calculating Teamstats -----<br>");
				container.scrollTop(container.prop("scrollHeight"));
				$("button.write."+tournament_id).removeClass("loading-data");
				currButton.children(".lds-dual-ring").remove();
				set_all_buttons_onclick(1,tournament_id);
			}
		}
	};
	teams_request.open("GET", "./ajax/get-data.php", true);
	teams_request.setRequestHeader("type", "teams");
	teams_request.setRequestHeader("tournamentID", tournament_id);
	teams_request.send();
}


function change_tournament(tournament_id) {
	$("div.writing-wrapper").addClass("hidden");
	$("div.writing-wrapper."+tournament_id).removeClass("hidden");
}