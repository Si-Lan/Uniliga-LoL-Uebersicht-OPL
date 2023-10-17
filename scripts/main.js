$(document).ready(() => {
	$(".settings-option.login").on("click", () => {event.preventDefault(); document.getElementById("login-dialog").showModal();});
	$('dialog.dismissable-popup').on('click', function (event) {
		if (event.target === this) {
			this.close();
		}
	});
	$("dialog.modalopen_auto").get().forEach(element => element.showModal());
	$("dialog .close-popup").on("click", function() {this.closest("dialog").close()})
});

// allgemeine Funktionen der Seite
async function open_settings_menu() {
	event.preventDefault();
	$('.settings-menu').toggleClass('shown');
}
async function toggle_darkmode() {
	event.preventDefault();
	document.getElementsByTagName("body")[0].style.transition = "none";
	let tags = document.getElementsByTagName("body")[0].getElementsByTagName("*");
	let tag_num = tags.length;
	for (let i = 0; i < tag_num; i++) {
		tags[i].style.transition = "none";
	}
	let cookie_expiry = new Date();
	cookie_expiry.setFullYear(cookie_expiry.getFullYear()+1);
	let body = $('body');
	if (body.hasClass("light")) {
		body.removeClass("light");
		await new Promise(r => setTimeout(r, 1));
		$('.settings-option.toggle-mode').html(get_material_icon("dark_mode"));
		document.cookie = `lightmode=0; expires=${cookie_expiry}; path=/`;
	} else {
		body.addClass("light");
		await new Promise(r => setTimeout(r, 1));
		$('.settings-option.toggle-mode').html(get_material_icon("light_mode"));
		document.cookie = `lightmode=1; expires=${cookie_expiry}; path=/`;
	}
	await new Promise(r => setTimeout(r, 10));
	document.getElementsByTagName("body")[0].style.transition = null;
	for (let i = 0; i < tag_num; i++) {
		tags[i].style.transition = null;
	}
}
function toggle_admin_buttons() {
	event.preventDefault();
	let cookie_expiry = new Date();
	cookie_expiry.setFullYear(cookie_expiry.getFullYear()+1);
	let body = $('body');
	if (body.hasClass("admin_li")) {
		body.removeClass("admin_li");
		$('.settings-option.toggle-admin-b-vis').html(`Buttons${get_material_icon("visibility_off")}`);
		document.cookie = `admin_btns=0; expires=${cookie_expiry}; path=/`;
	} else {
		body.addClass("admin_li");
		$('.settings-option.toggle-admin-b-vis').html(`Buttons${get_material_icon("visibility")}`);
		document.cookie = `admin_btns=1; expires=${cookie_expiry}; path=/`;
	}
}
$(document).ready(function () {
	$('header .settings-button').on("click",open_settings_menu);
	$('header .settings-option.toggle-mode').on("click",toggle_darkmode);
	let settings = $('.settings-menu');
	let header = $('header');
	window.addEventListener("click", (event) => {
		if (settings.hasClass('shown')) {
			if (!$.contains(header.get(0),$(event.target).get(0)) && event.target !== header[0]) {
				settings.removeClass('shown');
			}
		}
	});
	$('header .settings-option.toggle-admin-b-vis').on("click",toggle_admin_buttons);
});
$(document).ready(function() {
	let encMail = "aW5mb0BzaWxlbmNlLmxvbA==";
	$(".settings-option.feedback").attr("href",`mailto:${atob(encMail)}`);
});

function clear_searchbar() {
	event.preventDefault();
	$('.searchbar input').val('').trigger('keyup').trigger('input').focus();
}
function toggle_clear_search_x() {
	let clear_button = $(".searchbar .material-symbol");
	let input = $('.searchbar input')[0].value;
	if (input === "") {
		clear_button.css("display", "none");
	} else {
		clear_button.css("display", "block");
	}
}
$(document).ready(function() {
	$('.searchbar input').on("input",toggle_clear_search_x);
	$('.searchbar .clear-search').on("click",clear_searchbar);
});

// teamlist-filter
function update_team_filter_groups(div_id) {
	if (div_id === "all") {
		$("select.groups").empty().append("<option value='all' selected='selected'>Alle Gruppen</option>");
	} else {
		fetch(`ajax/get-data.php`, {
			method: "GET",
			headers: {
				"type": "groups",
				"tournamentID": div_id,
			}
		})
			.then(res => res.json())
			.then(groups => {
				let groupslist = $("select.groups");
				groupslist.empty().append("<option value='all' selected='selected'>Alle Gruppen</option>")
				for (let i = 0; i < groups.length; i++) {
					groupslist.append(`<option value='${groups[i]["OPL_ID"]}'>Gruppe ${groups[i]["number"]}</option>`);
				}
			})
			.catch(error => console.error(error));
	}
}
function filter_teams_list_division(division) {
	update_team_filter_groups(division);
	let liste = document.getElementsByClassName("team-list")[0];
	let tags = liste.querySelectorAll('div.team-button');
	let results = tags.length;
	for (let i = 0; i < tags.length; i++) {
		let value = tags[i].classList;
		if (value.contains(division) || division === "all") {
			if (tags[i].classList.contains("filterD-off")) {
				tags[i].classList.remove("filterD-off");
			}
			if (tags[i].classList.contains("filterG-off")) {
				tags[i].classList.remove("filterG-off");
			}
		} else {
			if (!(tags[i].classList.contains("filterD-off"))) {
				tags[i].classList.add("filterD-off");
			}
			results -= 1;
		}
	}
	if (results === 0) {
		document.getElementsByClassName('no-search-res-text')[0].style.display = "";
	} else {
		document.getElementsByClassName('no-search-res-text')[0].style.display = "none";
	}

	let group_button = $('div.team-filter-wrap a.b-group');
	group_button.removeClass('shown')


	let url = new URL(window.location.href);
	if (division !== url.searchParams.get('liga') || (division === "all" && url.searchParams.get('liga') == null)) {
		if (division === "all") {
			url.searchParams.delete('liga');
		} else {
			url.searchParams.set('liga',division);
		}
		url.searchParams.delete('gruppe');
		window.history.pushState({}, '', url);
	}
}
function filter_teams_list_group(group) {
	let liste = document.getElementsByClassName("team-list")[0];
	let tags = liste.querySelectorAll('div.team-button');
	let results = tags.length;
	for (let i = 0; i < tags.length; i++) {
		let value = tags[i].classList;
		if (value.contains(group) || group === "all") {
			if (tags[i].classList.contains("filterG-off")) {
				tags[i].classList.remove("filterG-off");
			}
		} else {
			if (!(tags[i].classList.contains("filterG-off"))) {
				tags[i].classList.add("filterG-off");
			}
			results -= 1;
		}
	}
	if (results === 0) {
		document.getElementsByClassName('no-search-res-text')[0].style.display = "";
	} else {
		document.getElementsByClassName('no-search-res-text')[0].style.display = "none";
	}

	let url = new URL(window.location.href);

	let group_button = $('div.team-filter-wrap a.b-group');
	if (group === "all") {
		group_button.removeClass('shown')
	} else {
		group_button.addClass('shown');
		if (url.searchParams.get('tournament') === null) {
			group_button.attr('href',`turnier/${url.pathname.split("turnier/")[1].split("/")[0]}/gruppe/${group}`);
		} else {
			group_button.attr('href',`?page=group&tournament=${url.searchParams.get('tournament')}&group=${group}`);
		}
	}

	if (group !== url.searchParams.get('gruppe') || (group === "all" && url.searchParams.get('gruppe') == null)) {
		if (group === "all") {
			url.searchParams.delete('gruppe');
		} else {
			url.searchParams.set('gruppe', group);
		}
		window.history.pushState({}, '', url);
	}
}


// handle search
function search_teams(tournID) {
	// Declare variables
	let input, filter, liste, tags, i, txtValue;
	let results;
	input = document.getElementsByClassName("search-teams " + tournID)[0];
	filter = input.value.toUpperCase();
	liste = document.getElementsByClassName("team-list " + tournID)[0];
	tags = liste.querySelectorAll('div.team-button');
	results = tags.length;

	// Loop through all list items, and hide those who don't match the search query
	for (i = 0; i < tags.length; i++) {
		txtValue = tags[i].innerText;
		if (txtValue.toUpperCase().indexOf(filter) > -1) {
			if (tags[i].classList.contains("search-off")) {
				tags[i].classList.remove("search-off");
			}
		} else {
			if (!(tags[i].classList.contains("search-off"))) {
				tags[i].classList.add("search-off");
			}
			results -= 1;
		}
	}
	if (results === 0) {
		document.getElementsByClassName(`no-search-res-text ${tournID}`)[0].style.display = "";
	} else {
		document.getElementsByClassName(`no-search-res-text ${tournID}`)[0].style.display = "none";
	}
}

function search_tourns() {
	// Declare variables
	let input, filter, liste, tags, i, txtValue;
	input = document.getElementsByClassName("search-tournaments")[0];
	filter = input.value.toUpperCase();
	liste = document.getElementById("turnier-select");
	tags = liste.querySelectorAll('.turnier-button');

	// Loop through all list items, and hide those who don't match the search query
	for (i = 0; i < tags.length; i++) {
		let tournID = tags[i].classList[1];
		txtValue = tags[i].innerText;
		if (txtValue.toUpperCase().indexOf(filter) > -1) {
			tags[i].style.display = "";
		} else {
			let team_list = document.querySelector(`div.team-list-wrap.${CSS.escape(tournID)}.slide`);
			if (team_list != null) {
				team_list.classList.toggle('slide');
			}
			tags[i].style.display = "none";
		}
	}
}


// OPGG on Summoner Cards
function player_to_opgg_link(player_id, player_name) {
	let checkbox = $(`.summoner-card.${player_id} input.opgg-checkbox`);
	let opgg_button = $('div.opgg-cards a.op-gg');
	let opgg_button_num = $('div.opgg-cards a.op-gg span.player-amount');
	let opgg_link = new URL(opgg_button.attr('href'));
	let players = opgg_link.searchParams.get('summoners');
	let player_amount;
	if (checkbox.prop('checked')) {
		players = players.split(",");
		players = players.filter(function(e) { return e !== player_name});
		player_amount = players.length;
		players = players.join(",");
		checkbox.prop('checked', false);
	} else {
		if (players === "") {
			players = player_name;
			player_amount = 1;
		} else {
			players = players.split(",");
			players.push(player_name);
			player_amount = players.length;
			players = players.join(",");
		}
		checkbox.prop('checked', true);
	}
	opgg_link.searchParams.set('summoners', players);
	opgg_button.attr('href', opgg_link);
	opgg_button_num.text(`(${player_amount} Spieler)`);
}

// open team-popup
let current_team_in_popup = null;
async function popup_team(teamID, tournamentID = null) {
	event.preventDefault();
	let popup = $('.team-popup');
	let popupbg = $('.team-popup-bg');
	let pagebody = $('body');
	let opgg_logo_svg = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" +
		"<svg width=\"338px\" height=\"83px\" viewBox=\"0 0 338 83\" version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\">\n" +
		"    <!-- Generator: sketchtool 52.4 (67378) - http://www.bohemiancoding.com/sketch -->\n" +
		"    <title>7D32657F-0BD6-4C11-9E1F-31F1C1B9B19C</title>\n" +
		"    <desc>Created with sketchtool.</desc>\n" +
		"    <g id=\"desktop\" stroke=\"none\" stroke-width=\"1\" fill=\"none\" fill-rule=\"evenodd\">\n" +
		"        <g id=\"index_black_asset\" transform=\"translate(-1320.000000, -1206.000000)\" fill=\"#FFFFFF\" fill-rule=\"nonzero\">\n" +
		"            <g id=\"img_opgglogo\" transform=\"translate(1320.000000, 1205.000000)\">\n" +
		"                <path d=\"M41.0445473,64.2304516 C29.1526673,64.2304516 19.4777351,54.458781 19.4777351,42.4467811 C19.4777351,30.4357693 29.1526673,20.6631105 41.0445473,20.6631105 C52.9334922,20.6631105 62.6084244,30.4357693 62.6084244,42.4467811 C62.6084244,54.458781 52.9334922,64.2304516 41.0445473,64.2304516 M41.0455257,0.988334115 C18.4132871,0.988334115 -0.000293505881,19.5859341 -0.000293505881,42.4457928 C-0.000293505881,65.307628 18.4132871,83.9062162 41.0455257,83.9062162 C63.6748291,83.9062162 82.0864531,65.307628 82.0864531,42.4457928 C82.0864531,19.5859341 63.6748291,0.988334115 41.0455257,0.988334115\" id=\"Fill-1\"></path>\n" +
		"                <path d=\"M130.635554,45.0264705 L108.325194,45.0264705 L108.325194,20.1683999 L130.635554,20.1683999 C138.333235,20.1683999 140.680304,27.4882587 140.680304,32.5974352 C140.680304,37.8647293 138.333235,45.0264705 130.635554,45.0264705 M160.066368,32.5974352 C160.066368,14.7726353 148.012081,1.00552941 130.726541,1.00552941 L89.9096567,1.00552941 L89.9096567,83.8364468 L108.322259,83.8364468 L108.325194,64.189341 L130.726541,64.189341 C147.391805,64.189341 160.066368,50.5151293 160.066368,32.5974352\" id=\"Fill-4\"></path>\n" +
		"                <path d=\"M251.126763,37.4563905 L214.564735,37.4563905 L214.564735,52.2868375 L231.320008,52.2868375 C229.984556,58.8240139 223.172284,64.683261 212.901535,64.683261 C200.752348,64.683261 190.86707,54.6991198 190.86707,42.4291905 C190.86707,30.1553081 200.752348,20.5417552 212.901535,20.5417552 C218.861661,20.5417552 224.449035,22.5547905 228.640299,26.8842493 L229.570713,27.8467905 L230.679187,27.1006729 L243.940761,18.1680141 L245.434706,17.1619905 L244.278292,15.7745082 C236.462231,6.38824939 225.023328,1.00533176 212.901535,1.00533176 C190.291799,1.00533176 171.895829,19.5881082 171.895829,42.4291905 C171.895829,65.2682963 190.291799,83.8471198 212.901535,83.8471198 C233.027234,83.8471198 247.462831,72.1444374 250.572037,53.3057081 C251.339066,48.6491434 251.54452,46.2862728 251.408529,42.4291905 C251.328304,40.1305552 251.215793,38.4900846 251.126763,37.4563905\" id=\"Fill-6\"></path>\n" +
		"                <path d=\"M337.241878,37.4669646 L300.678872,37.4669646 L300.678872,52.2964234 L317.434144,52.2964234 C316.100649,58.8335998 309.286421,64.6938351 299.014693,64.6938351 C286.866485,64.6938351 276.981207,54.709694 276.981207,42.4387763 C276.981207,30.164894 286.866485,20.5513411 299.014693,20.5513411 C304.973841,20.5513411 310.561215,22.5653646 314.755414,26.8938352 L315.685827,27.8563764 L316.794301,27.1102587 L330.054897,18.1785882 L331.54982,17.1715764 L330.393407,15.7840941 C322.57441,6.39882351 311.137465,1.01590588 299.014693,1.01590588 C276.403979,1.01590588 258.010943,19.5976941 258.010943,42.4387763 C258.010943,65.2788704 276.403979,83.8576939 299.014693,83.8576939 C319.142348,83.8576939 333.576968,72.1550116 336.686173,53.315294 C337.45418,48.6597175 337.658656,46.2958587 337.522665,42.4397646 C337.44244,40.1401411 337.32993,38.5006587 337.241878,37.4669646\" id=\"Fill-8\"></path>\n" +
		"                <path d=\"M160.064411,67.0291339 C155.477892,67.0291339 151.745476,70.7992516 151.745476,75.4340751 C151.745476,80.0669221 155.477892,83.8370398 160.064411,83.8370398 C164.652886,83.8370398 168.386281,80.0669221 168.386281,75.4340751 C168.386281,70.7992516 164.652886,67.0291339 160.064411,67.0291339\" id=\"Fill-10\"></path>\n" +
		"            </g>\n" +
		"        </g>\n" +
		"    </g>\n" +
		"</svg>";

	if (current_team_in_popup === teamID) {
		popupbg.css("opacity","0");
		popupbg.css("display","block");
		await new Promise(r => setTimeout(r, 10));
		popupbg.css("opacity","1");
		pagebody.addClass("popup_open");
		return;
	}

	current_team_in_popup = parseInt(teamID);
	popup.empty();

	popup.append(`<div class='close-button' onclick='closex_popup_team()'>${get_material_icon("close")}</div>`);
	popup.append("<div class='close-button-space'><div class='popup-loading-indicator'></div></div>");

	popupbg.css("opacity","0");
	popupbg.css("display","block");
	await new Promise(r => setTimeout(r, 10));
	popupbg.css("opacity","1");
	pagebody.addClass("popup_open");

	fetch(`ajax/get-data.php`, {
		method: "GET",
		headers: {
			type: "team-and-players",
			teamid: teamID,
			tournamentid: tournamentID,
		}
	})
		.then(res => res.json())
		.then(team_data => {
			if (current_team_in_popup === team_data['team']['OPL_ID']) {

				let players_string = "";
				for (let i = 0; i < team_data["players"].length; i++) {
					if (i !== 0) {
						players_string += encodeURIComponent(",");
					}
					players_string += encodeURIComponent(team_data["players"][i]['summonerName']);
				}

				popup.append("<div class='team-buttons opgg-cards'></div>");
				let name_container = $("div.team-buttons");
				if (team_data["team"]["OPL_ID_logo"] !== null && team_data["team"]["OPL_ID_logo"] !== "") {
					fetch(`img/team_logos/${team_data["team"]["OPL_ID_logo"]}/logo.webp`, {method:"HEAD"})
						.then(res => {
							if (res.ok) {
								name_container.prepend(`<img class='list-overview-logo' src='img/team_logos/${team_data["team"]["OPL_ID_logo"]}/logo.webp' alt='Team-Logo'>`)
							}
						})
						.catch(e => console.error(e));
				}
				name_container.append(`<h2>${team_data["team"]["name"]}</h2>`);
				name_container.append(`<a href='https://www.opleague.pro/team/${teamID}' target='_blank' class='toorlink'>${get_material_icon("open_in_new")}</a>`);
				name_container.append(`<a href='https://www.op.gg/multisearch/euw?summoners=${players_string}' target='_blank' class='button op-gg'><div class='svg-wrapper op-gg'>${opgg_logo_svg}</div><span class='player-amount'>(${team_data["players"].length} Spieler)</span></a>`);
				name_container.append(`<a href='turnier/${tournamentID}/team/${teamID}' class='button'>${get_material_icon("info")}Team-Übersicht</a>`);
				let sc_collapsed = getCookie("preference_sccollapsed");
				if (sc_collapsed === "1") {
					popup.append(`<button type="button" class="exp_coll_sc">${get_material_icon("unfold_more")}Stats ein</button>`)
				} else {
					popup.append(`<button type="button" class="exp_coll_sc">${get_material_icon("unfold_less")}Stats aus</button>`)
				}
				$('button.exp_coll_sc').on("click",expand_collapse_summonercard);
				if (team_data["team"]["avg_rank_tier"] !== null && team_data["team"]["avg_rank_tier"] !== "") {
					team_data["team"]["avg_rank_tier"] = team_data["team"]["avg_rank_tier"][0].toUpperCase() + team_data["team"]["avg_rank_tier"].substring(1).toLowerCase();
					popup.append("<div class='team-avg-rank'>Teams avg. Rang: <img class='rank-emblem-mini' src='ddragon/img/ranks/mini-crests/" + team_data["team"]["avg_rank_tier"].toLowerCase() + ".svg' alt=''><span>" + team_data["team"]["avg_rank_tier"] + " " + team_data["team"]["avg_rank_div"] + "</span></div>");
				}
				popup.append("<div class='summoner-card-container'></div>");
				let card_container = $('div.summoner-card-container');

				let coll_class = (sc_collapsed === "1") ? "collapsed" : "";
				for (let i = 0; i < team_data["players"].length; i++) {
					card_container.append(`<div class='summoner-card-wrapper placeholder p${i} ${coll_class}'></div>`);
				}

				fetch(`ajax/summoner-card.php`, {
					method: "GET",
					headers: {
						teamid: teamID,
						tournamentID: tournamentID,
					}
				})
					.then(res => res.json())
					.then(async card_results => {
						for (let i = 0; i < card_results.length; i++) {
							card_container.find(".placeholder.p" + i).replaceWith(card_results[i]);
						}
						let popup_loader = $('.popup-loading-indicator');
						popup_loader.css("opacity", "0");
						await new Promise(r => setTimeout(r, 210));
						popup_loader.remove();
					})
					.catch(error => console.error(error));
			}
		})
		.catch(error => console.error(error));
}
async function close_popup_team(event) {
	let popupbg = $('.team-popup-bg');
	if (event.target === popupbg[0]) {
		popupbg.css("opacity","0");
		await new Promise(r => setTimeout(r, 250));
		$("body").removeClass("popup_open")
		popupbg.css("display","none");
	}
}
async function closex_popup_team() {
	let popupbg = $('.team-popup-bg');
	popupbg.css("opacity","0");
	await new Promise(r => setTimeout(r, 250));
	$("body").removeClass("popup_open")
	popupbg.css("display","none");
}
$(document).ready(function () {
	let body = $('body');
	if (body.hasClass("teamlist") || body.hasClass("elo-overview")) {
		window.addEventListener("keydown", (event) => {
			if (event.key === "Escape") {
				closex_popup_team();
			}
		})
	}
});

// open match popup
let current_match_in_popup = null;
$(document).ready(function() {
	let url = new URL(window.location.href);
	current_match_in_popup = url.searchParams.get('match');
});
async function popup_match(matchID,teamID=null,matchtype="groups") {
	event.preventDefault();
	let popup = $('.mh-popup');
	let popupbg = $('.mh-popup-bg');
	let pagebody = $("body");

	if (current_match_in_popup === matchID) {
		popupbg.css("opacity","0");
		popupbg.css("display","block");
		await new Promise(r => setTimeout(r, 10));
		popupbg.css("opacity","1");
		pagebody.css("popup_open");
		let url = new URL(window.location.href);
		url.searchParams.set("match",matchID);
		window.history.replaceState({}, '', url);
		return;
	}

	current_match_in_popup = parseInt(matchID);
	popup.empty();

	popup.append(`<div class='close-button' onclick='closex_popup_match()'>${get_material_icon("close")}</div>`);
	popup.append(`<div class='close-button-space'><div class='popup-loading-indicator'></div></div>`);

	popupbg.css("opacity","0");
	popupbg.css("display","block");
	await new Promise(r => setTimeout(r, 10));
	popupbg.css("opacity","1");
	pagebody.addClass("popup_open");
	let url = new URL(window.location.href);
	url.searchParams.set("match",matchID);
	window.history.replaceState({}, '', url);

	fetch(`ajax/get-data.php`, {
		method: "GET",
		headers: {
			type: "match-games-teams-by-matchid",
			matchid: matchID,
		}
	})
		.then(res => res.json())
		.then(async data => {
			let games = data['games'];

			let buttonwrapper = `<div class='mh-popup-buttons'>`;
			if (teamID != null) {
				buttonwrapper += `<a class='button' href='team/${teamID}/matchhistory#${matchID}'> ${get_material_icon("manage_search")} in Matchhistory ansehen</a>`;
			}
			let teamid_data = "";
			if (teamID !== null) teamid_data = `data-team='${teamID}'`;
			buttonwrapper += `<div class='updatebuttonwrapper'><button type='button' class='icononly user_update_match update_data' data-match='${matchID}' data-matchformat='${matchtype}' ${teamid_data}>${get_material_icon('sync')}</button><span>letztes Update:<br>&nbsp;</span></div>`;
			buttonwrapper += "</div>";
			popup.append(buttonwrapper);

			$(".user_update_match").on("click", function () {
				user_update_match(this);
			});

			fetch(`ajax/get-data.php`, {
				method: "GET",
				headers: {
					type: "last-update-time",
					itemid: matchID,
					updatetype: "match",
					relativetime: "true",
				}
			})
				.then(res => res.text())
				.then(time => {
					$(".mh-popup .updatebuttonwrapper span").html(`letztes Update:<br>${time}`);
				})
				.catch(error => console.error(error));

			let team1score;
			let team2score;
			if (data['match']['winner'] === data["team1"]["OPL_ID"]) {
				team1score = "win";
				team2score = "loss";
			} else if (data['match']['winner'] === data["team2"]["OPL_ID"]) {
				team1score = "loss";
				team2score = "win";
			} else {
				team1score = "draw";
				team2score = "draw";
			}
			let team1wins = data['match']['team1Score'];
			let team2wins = data['match']['team2Score'];
			if (team1wins === -1 || team2wins === -1) {
				team1wins = (team1wins === -1) ? "L" : "W";
				team2wins = (team2wins === -1) ? "L" : "W";
			}
			if (current_match_in_popup === data['match']['OPL_ID']) {
				popup.append(`<h2 class='round-title'>
                                <span class='round'>Runde ${data['match']['playday']}: &nbsp</span>
                                <a href='team/${data['team1']['OPL_ID']}' class='team "+team1score+"'>${data['team1']['name']}</a>
                                <span class='score'><span class='${team1score}'>${team1wins}</span>:<span class='${team2score}'>${team2wins}</span></span>
                                <a href='team/${data['team2']['OPL_ID']}' class='team ${team2score}'>${data['team2']['name']}</a>
                              </h2>`);
			}
			if (games.length === 0) {
				popup.append("<div class='no-game-found'>Keine Spieldaten gefunden</div>");
				let popup_loader = $('.popup-loading-indicator');
				popup_loader.css("opacity", "0");
				await new Promise(r => setTimeout(r, 210));
				popup_loader.remove();
			}
			let game_counter = 0;
			for (const [i, game] of games.entries()) {
				if (current_match_in_popup === game['OPL_ID_match']) {
					popup.append(`<div class='game game${i}'></div>`);
				}
				let gameID = game['RIOT_matchID'];

				let fetchheaders = new Headers({
					gameid: gameID
				});
				if (teamID !== null) {
					fetchheaders.append("teamid",teamID)
				}
				fetch(`ajax/game.php`, {
					method: "GET",
					headers: fetchheaders,
				})
					.then(res => res.text())
					.then(async data => {
						let game_wrap = popup.find(`.game${i}`);
						if (current_match_in_popup === game['OPL_ID_match']) {
							game_wrap.empty();
							game_wrap.append(data);
							game_counter++;
							if (game_counter >= games.length) {
								let popup_loader = $('.popup-loading-indicator');
								popup_loader.css("opacity", "0");
								await new Promise(r => setTimeout(r, 210));
								popup_loader.remove();
							}
						}
					})
					.catch(error => console.error(error));
			}
		})
		.catch(error => console.log(error));
}
async function close_popup_match(event) {
	let popupbg = $('.mh-popup-bg');
	if (event.target === popupbg[0]) {
		popupbg.css("opacity","0");
		let url = new URL(window.location.href);
		url.searchParams.delete("match");
		window.history.replaceState({}, '', url);
		await new Promise(r => setTimeout(r, 250));
		$("body").removeClass("popup_open");
		popupbg.css("display","none");
	}
}
async function closex_popup_match() {
	let popupbg = $('.mh-popup-bg');
	popupbg.css("opacity","0");
	let url = new URL(window.location.href);
	url.searchParams.delete("match");
	window.history.replaceState({}, '', url);
	await new Promise(r => setTimeout(r, 250));
	$("body").removeClass("popup_open");
	popupbg.css("display","none");
}
$(document).ready(function () {
	let body = $('body');
	if (body.hasClass("team") || body.hasClass("group")) {
		window.addEventListener("keydown", (event) => {
			if (event.key === "Escape") {
				closex_popup_match();
			}
		})
	}
});

// Elo Overview Swap Views
function switch_elo_view(tournamentID,view) {
	event.preventDefault();
	let url = new URL(window.location.href);
	let area = $('.main-content');
	let but = $('.filter-button-wrapper .button');
	let all_b = $('.filter-button-wrapper .all-teams');
	let div_b = $('.filter-button-wrapper .div-teams');
	let group_b = $('.filter-button-wrapper .group-teams');
	let color_b = $('.settings-button-wrapper .button span');

	if (view === "all-teams") {
		but.removeClass('active');
		all_b.addClass('active');
		url.searchParams.delete("view");
		window.history.replaceState({}, '', url);
		add_elo_team_list(area,tournamentID,"all");
		color_b.text("Nach Liga einfärben");
	} else if (view === "div-teams") {
		but.removeClass('active');
		div_b.addClass('active');
		url.searchParams.set("view","liga");
		window.history.replaceState({}, '', url);
		add_elo_team_list(area,tournamentID,"div");
		color_b.text("Nach Rang einfärben");
	} else if (view === "group-teams") {
		but.removeClass('active');
		group_b.addClass('active');
		url.searchParams.set("view","gruppe");
		window.history.replaceState({}, '', url);
		add_elo_team_list(area,tournamentID,"group");
		color_b.text("Nach Rang einfärben");
	}
}

function jump_to_league_elo(div_num) {
	event.preventDefault();
	let league = $(`.teams-elo-list h3.liga${div_num}`);
	$('html').stop().animate({scrollTop: league[0].offsetTop-40}, 300, 'swing');
}

let elo_list_fetch_control = null;
function add_elo_team_list(area,tournamentID,type) {
	let displaytype = "none";
	if (type === "div" || type === "group") {
		displaytype = ""
	}

	if (elo_list_fetch_control !== null) elo_list_fetch_control.abort();
	elo_list_fetch_control = new AbortController();

	fetch(`ajax/elo-list-ajax.php`, {
		method: "GET",
		headers: {
			"TournamentID": tournamentID,
			"type": type,
		},
		signal: elo_list_fetch_control.signal,
	})
		.then(res => res.text())
		.then(list => {
			area.empty();
			area.append(list);
			$('.jump-button-wrapper').css("display",displaytype);
		})
		.catch(error => {
			if (error.name === "AbortError") {
				console.warn(error)
			} else {
				console.error(error)
			}
		});
}

function color_elo_list() {
	event.preventDefault();
	let url = new URL(window.location.href);
	let checkbox = $('input.color-checkbox');
	if (checkbox.prop('checked')) {
		$('.main-content').removeClass('colored-list');
		checkbox.prop('checked', false);
		url.searchParams.delete("colored");
		window.history.replaceState({}, '', url);
	} else {
		$('.main-content').addClass('colored-list');
		checkbox.prop('checked', true);
		url.searchParams.set("colored","true");
		window.history.replaceState({}, '', url);
	}
}

let acCurrentFocus = -1;
function search_teams_elo() {
	let searchbar = $('.search-wrapper .searchbar');
	let input = $('.search-teams-elo')[0];
	let input_value = input.value.toUpperCase();
	let ac = $('.search-wrapper .searchbar .autocomplete-items');

	if (ac.length === 0) {
		ac = $("<div class=\'autocomplete-items\'></div>");
		searchbar.append(ac);
	} else {
		ac.empty();
	}
	acCurrentFocus = -1;

	let teams = $('.elo-list-team');
	teams.removeClass('ac-selected-team');

	if (input_value === "") {
		return;
	}
	let teams_list = [];
	for (const team of teams) {
		let team_name = $(team).find('.elo-list-item.team span')[0];
		teams_list.push([team_name.innerText,team_name.offsetTop,$(team)[0].classList[2]]);
	}
	teams_list.sort(function(a,b) {return a[0] > b[0] ? 1 : -1});

	for (let i=0; i < teams_list.length; i++) {
		let indexOf = teams_list[i][0].toUpperCase().indexOf(input_value);
		if (indexOf > -1) {
			ac.append($(`<div>${teams_list[i][0].substring(0,indexOf)}<strong>${teams_list[i][0].substring(indexOf,indexOf+input_value.length)}</strong>${teams_list[i][0].substring(indexOf+input_value.length)}
                    <input type='hidden' value='${teams_list[i][1]}'></div>`).click(function() {
				$('html').stop().animate({scrollTop: this.getElementsByTagName("input")[0].value-300}, 400, 'swing');
				$('.search-wrapper .searchbar .autocomplete-items').empty();
				$('.search-wrapper .searchbar input').val("");
				$(".searchbar .material-symbol").css("display","none");
				$('.elo-list-team').removeClass('ac-selected-team');
				$('.elo-list-team.'+teams_list[i][2]).addClass('ac-selected-team');
			}));
		}
	}

	if (!($(input).hasClass("focus-listen"))) {
		input.addEventListener("keydown",function (e) {
			let autocomplete = $('.search-wrapper .searchbar .autocomplete-items');
			let autocomplete_items = $('.search-wrapper .searchbar .autocomplete-items div');
			if(autocomplete_items.length > 0) {
				if (e.keyCode === 40) {
					e.preventDefault();
					acCurrentFocus++;
					autocomplete_items.removeClass("autocomplete-active");
					if (acCurrentFocus >= autocomplete_items.length) acCurrentFocus = 0;
					if (acCurrentFocus < 0) acCurrentFocus = (autocomplete_items.length - 1);
					autocomplete_items[acCurrentFocus].classList.add("autocomplete-active");
					if (!(autocomplete[0].scrollTop+autocomplete[0].offsetHeight-autocomplete_items[acCurrentFocus].offsetHeight >= autocomplete_items[acCurrentFocus].offsetTop) || !(autocomplete[0].scrollTop <= autocomplete_items[acCurrentFocus].offsetTop)) {
						autocomplete.stop().animate({scrollTop: autocomplete_items[acCurrentFocus].offsetTop-autocomplete[0].offsetHeight+autocomplete_items[acCurrentFocus].offsetHeight}, 100, 'swing');
					}
				} else if (e.keyCode === 38) {
					e.preventDefault();
					acCurrentFocus--;
					autocomplete_items.removeClass("autocomplete-active");
					if (acCurrentFocus >= autocomplete_items.length) acCurrentFocus = 0;
					if (acCurrentFocus < 0) acCurrentFocus = (autocomplete_items.length - 1);
					autocomplete_items[acCurrentFocus].classList.add("autocomplete-active");
					if (!(autocomplete[0].scrollTop+autocomplete[0].offsetHeight-autocomplete_items[acCurrentFocus].offsetHeight >= autocomplete_items[acCurrentFocus].offsetTop) || !(autocomplete[0].scrollTop <= autocomplete_items[acCurrentFocus].offsetTop)) {
						autocomplete.stop().animate({scrollTop: autocomplete_items[acCurrentFocus].offsetTop}, 100, 'swing');
					}
				} else if (e.keyCode === 13) {
					if (acCurrentFocus < 0) acCurrentFocus = 0;
					e.preventDefault();
					autocomplete_items[acCurrentFocus].click();
				}
			}
		});
		$(input).addClass("focus-listen");
	}
}

function to_top() {
	$('html').stop().animate({scrollTop: 0}, 300, 'swing');
}

// TO-DO: put this only on elo-overview
async function hide_top_button() {
	let page = $('html');
	let button = $('a.button.totop');
	if (page[0].scrollTop > 100) {
		button.css("opacity","1");
		button.css("pointer-events","auto");
	} else {
		button.css("opacity","0");
		button.css("pointer-events","none");
	}
}
window.onscroll= hide_top_button;

// navigation buttons
function tournament_nav_switch_active() {
	if (user_update_running) {
		return;
	}
	$('.turnier-bonus-buttons .active').removeClass('active');
	$(this).addClass('active');
}
function team_nav_switch_active() {
	if (user_update_running) {
		return;
	}
	$('.team-titlebutton-wrapper .active').removeClass('active');
	$(this).addClass('active');
}
$(document).ready(function () {
	$('.turnier-bonus-buttons .button').on("click",tournament_nav_switch_active);
	$('.team-titlebutton-wrapper .button').on("click",team_nav_switch_active);
});

// teamstats table functions
function sort_table() {
	let element = this;
	let column;
	$(element).parent().find('th').each(function(index) {
		if (this === element) {
			column = index;
			return false;
		}
	});
	let direction = "desc";
	let reverse = "asc";
	let table = $(element).parent().parent().parent();
	let header_row = table.find("tr").first();
	let header_cells = header_row.find("th");
	let header_cell_prev = header_row.find('th.sortedby').first();
	let header_cell_current = header_row.find("th").eq(column);
	let newcolumn = !header_cell_current.hasClass("sortedby");
	if (newcolumn) {
		header_cells.removeClass("sortedby");
		header_cell_current.addClass("sortedby");
	} else {
		if (header_cell_current.hasClass("desc")) {
			direction = "asc";
			reverse = "desc";
		}
	}
	let rows = table.find("tr:not(.expand-table):not(:first)");
	let sorting_array = [];
	for (let i=0; i<rows.length; i++) {
		sorting_array.unshift([rows.eq(i),rows.eq(i).find("td").eq(column).text()]);
	}
	function custom_parseInt(int) {
		if (int === "-") {
			return -1;
		} else {
			return parseInt(int);
		}
	}
	if (direction === "asc") {
		sorting_array.sort(function (a, b) {
			return custom_parseInt(b[1]) - custom_parseInt(a[1]);
		});
	} else {
		sorting_array.sort(function (a, b) {
			return custom_parseInt(a[1]) - custom_parseInt(b[1]);
		});
	}
	rows.remove();
	let head = table.find("tr:first");
	for (let i=0; i<sorting_array.length; i++) {
		head.after(sorting_array[i][0]);
	}
	header_cells.removeClass("asc");
	header_cells.removeClass("desc");
	header_cell_current.addClass(direction);
	header_cell_current.removeClass(reverse);
	if (newcolumn) {
		swap_sort_icons(header_cell_current,header_cell_prev);
	}

	rows = table.find("tr:not(.expand-table):not(:first)");
	rows.on("mouseover",mark_champ_in_table);
	rows.on("mouseleave",stop_mark_champ_in_table);
	rows.on("click",hard_mark_champ_in_table);
}
$(document).ready(function () {
	$('.stattables table th.sortable').on("click",sort_table);
});
async function swap_sort_icons(header_cell_current,header_cell_prev) {
	await unset_prev_sorticon(header_cell_prev);
	await set_current_sorticon(header_cell_current);
}
async function unset_prev_sorticon(header_cell_prev) {
	header_cell_prev.find("div.sort-direction").css("transition","transform 200ms");
	header_cell_prev.find("div.sort-direction").css("transform","rotateX(90deg)");
	await new Promise(r => setTimeout(r, 120));
	header_cell_prev.find("div.sort-direction").html(get_material_icon("check_indeterminate_small",true));
	header_cell_prev.find("div.sort-direction").css("transform","");
	header_cell_prev.find("div.sort-direction").css("transition","");
}
async function set_current_sorticon(header_cell_current) {
	header_cell_current.find("div.sort-direction").css("transition","transform 0s");
	header_cell_current.find("div.sort-direction").css("transform","rotateX(90deg)");
	header_cell_current.find("div.sort-direction").html(get_material_icon("expand_more",true));
	await new Promise(r => setTimeout(r, 10));
	header_cell_current.find("div.sort-direction").css("transition","transform 200ms");
	header_cell_current.find("div.sort-direction").css("transform","");
	header_cell_current.find("div.sort-direction").css("transition","");
}

function expand_collapse_table() {
	let expand_button = $(this);
	let table = expand_button.parent().parent();
	if (table.hasClass("collapsed")) {
		table.removeClass("collapsed");
		table.addClass("expanded");
		let rows = $(table.find('tr')[5]).nextUntil('tr.expand-table');
		rows.find('td').wrapInner("<div style='display: none;'/>").parent().find('td>div').slideDown("fast",function () {
			let $set = $(this);
			$set.replaceWith($set.contents());
		});
	} else if (table.hasClass("expanded")) {
		let rows = $(table.find('tr')[5]).nextUntil('tr.expand-table');
		rows.find('td').wrapInner("<div style='display: block;'/>").parent().find('td>div').slideUp("fast",function () {
			table.removeClass("expanded");
			table.addClass("collapsed");
			let $set = $(this);
			$set.replaceWith($set.contents());
		});
	}
}
$(document).ready(function () {
	$('.stattables table tr.expand-table').on("click",expand_collapse_table);
});

function collapse_all_tables() {
	let buttons = $('.playertable tr.expand-table');
	for (const expand_button of buttons) {
		let table = $(expand_button).parent().parent();
		if (table.hasClass("expanded")) {
			let rows = $(table.find('tr')[5]).nextUntil('tr.expand-table');
			rows.find('td').wrapInner("<div style='display: block;'/>").parent().find('td>div').slideUp("fast",function () {
				table.removeClass("expanded");
				table.addClass("collapsed");
				let $set = $(this);
				$set.replaceWith($set.contents());
			});
		}
	}
}
function expand_all_tables() {
	let buttons = $('.playertable tr.expand-table');
	for (const expand_button of buttons) {
		let table = $(expand_button).parent().parent();
		if (table.hasClass("collapsed")) {
			table.removeClass("collapsed");
			table.addClass("expanded");
			let rows = $(table.find('tr')[5]).nextUntil('tr.expand-table');
			rows.find('td').wrapInner("<div style='display: none;'/>").parent().find('td>div').slideDown("fast",function () {
				let $set = $(this);
				$set.replaceWith($set.contents());
			});
		}
	}
}
$(document).ready(function () {
	$('div.playertable-header a.pt-collapse-all').on("click",collapse_all_tables);
	$('div.playertable-header a.pt-expand-all').on("click",expand_all_tables);
});

function mark_champ_in_table() {
	let tr_selector = $('.stattables .table tr');
	let champ = $(this).find('img').attr('alt');
	let activate = tr_selector.has(`td img[alt=${champ}]`);
	tr_selector.removeClass('temp-markedrow');
	activate.addClass('temp-markedrow');
	tr_selector.each(function (i,e) {
		var styleAttr = $(e).attr('class');
		if (!styleAttr || styleAttr === '') {
			$(e).removeAttr('class');
		}
	});
}
function hard_mark_champ_in_table() {
	let tr_selector = $('.stattables .table tr');
	if ($(this).find('th').length === 0 && !$(this).hasClass("expand-table")) {
		let champ = $(this).find('img').attr('alt');
		let activate = tr_selector.has(`td img[alt=${champ}]`);
		if (activate.hasClass("markedrow")) {
			activate.removeClass("markedrow");
		} else {
			tr_selector.removeClass("markedrow");
			tr_selector.each(function (i,e) {
				var styleAttr = $(e).attr('class');
				if (!styleAttr || styleAttr === '') {
					$(e).removeAttr('class');
				}
			});
			activate.addClass("markedrow");
		}
	}
}
function stop_mark_champ_in_table() {
	let tr_selector = $('.stattables .table tr');
	tr_selector.removeClass('temp-markedrow');
	tr_selector.each(function (i,e) {
		var styleAttr = $(e).attr('class');
		if (!styleAttr || styleAttr === '') {
			$(e).removeAttr('class');
		}
	});
}
$(document).ready(function () {
	let table_row = $('.stattables .table tr');
	table_row.on("mouseover",mark_champ_in_table);
	table_row.on("mouseleave",stop_mark_champ_in_table);
	table_row.on("click",hard_mark_champ_in_table);
});

// Funktionen für custom Dropdowns
function open_dropdown_selection() {
	event.preventDefault();
	$(this).toggleClass('open-selection');
}
async function select_dropdown_option() {
	event.preventDefault();
	let selection = $(this);
	let button = selection.parent().parent().find(".button-dropdown");
	let icon_div = button.find('.material-symbol')[0].innerHTML;
	button.html(this.innerText + `<span class='material-symbol'>${icon_div}</span>`);
	selection.parent().find(".dropdown-selection-item").removeClass('selected-item');
	selection.addClass('selected-item');
	handle_dropdown_selection(button[0].getAttribute("data-dropdowntype"), selection[0].getAttribute("data-selection"));
	await new Promise(r => setTimeout(r, 1));
	button.removeClass("open-selection");
}
let patch_view_fetch_control = null;
function handle_dropdown_selection(type, selection) {
	if (type === "stat-tables") {
		let entTable = $(".champstattables.entire");
		let singTable = $(".champstattables.singles");
		if (selection === "all") {
			entTable.css("display","flex");
			singTable.css("display","none");
		} else if (selection === "single") {
			singTable.css("display","flex");
			entTable.css("display","none");
		}
	}
	if (type === "get-patches") {
		let element = $('dialog.add-patch-popup .add-patches-display');
		let loading_indicator = $('dialog.add-patch-popup .popup-loading-indicator');
		loading_indicator.css("display","");
		if (patch_view_fetch_control !== null) patch_view_fetch_control.abort();
		patch_view_fetch_control = new AbortController();
		fetch(`admin/ajax/ddragon-update.php`, {
			method: "GET",
			headers: {
				type: "add-patch-view",
				view: selection,
				limit: 10,
			},
			signal: patch_view_fetch_control.signal,
		})
			.then(res => res.text())
			.then(patches => {
				loading_indicator.css("display","none");
				element.html(patches);
				$(".add_patch").on("click", function () {
					add_new_patch(this);
				});
			})
	}
}
$(document).ready(function () {
	$('div.dropdown-selection .dropdown-selection-item').on("click",select_dropdown_option);
	$('.button-dropdown').on("click",open_dropdown_selection);
});

// stats toggle playertables
function select_player_table() {
	event.preventDefault();
	let buttonJ = $(this);
	let current_is = buttonJ.hasClass('selected-player-table');
	let summoner = this.innerText.split(" ");
	summoner.pop();
	summoner = summoner.join(" ");
	let table = $(`.playerstable h4:contains(${summoner})`).parent();
	if (!current_is){
		let marked_elsewhere = buttonJ.parent().parent().parent().find(`.role-playername.selected-player-table:contains(${summoner})`);
		marked_elsewhere.removeClass('selected-player-table');
		buttonJ.addClass('selected-player-table');
		let ind = 0;
		$('.teamroles .role:not(.svg-wrapper)').each(function(index) {
			if ($.contains(this,buttonJ[0])) {
				ind = index;
				return false;
			}
		});
		for (let i = 0; i < 5; i++) {
			table.removeClass(("role"+i));
		}
		for (let i = ind; i >= 0; i--) {
			let next_table = $(`.playertable.role${i}`);
			if (next_table.length > 0) {
				next_table.last().after(table);
				break;
			}
			if (i === 0) {
				$('.playertable').first().before(table);
			}
		}
		table.addClass(("role"+ind));
		table.removeClass('hidden-table');
	} else {
		buttonJ.removeClass('selected-player-table');
		table.addClass('hidden-table')
	}
}
$(document).ready(function () {
	$('div.roleplayers a.role-playername').on("click",select_player_table);
});

// toggle summonercard expansion
function expand_collapse_summonercard() {
	event.preventDefault();
	let sc = $(".summoner-card-wrapper .summoner-card");
	let collapse_button = $('.player-cards .exp_coll_sc');
	let cookie_expiry = new Date();
	cookie_expiry.setFullYear(cookie_expiry.getFullYear()+1);
	if (sc.hasClass("collapsed")) {
		sc.removeClass("collapsed");
		collapse_button.html(`${get_material_icon("unfold_less")}Stats aus`);
		document.cookie = `preference_sccollapsed=0; expires=${cookie_expiry}; path=/`;
	} else {
		sc.addClass("collapsed");
		collapse_button.html(`${get_material_icon("unfold_more")}Stats ein`);
		document.cookie = `preference_sccollapsed=1; expires=${cookie_expiry}; path=/`;
	}
}
$(document).ready(function () {
	$('.player-cards .exp_coll_sc').on("click",expand_collapse_summonercard);
});

// TODO: check code below (player-overview)

// player page search
let player_search_controller = null;
function search_players() {
	if (player_search_controller !== null) player_search_controller.abort();
	player_search_controller = new AbortController();

	let searchbar = $('.search-wrapper .searchbar');
	let input = $('input.search-players')[0];
	let input_value = input.value.toUpperCase();
	let player_list = $('.player-list');
	let recents_list = $('.recent-players-list');
	let loading_indicator = $('.search-loading-indicator');

	if (input_value.length < 2) {
		loading_indicator.remove();
		player_list.empty();
		recents_list.css("display",'');
		return;
	}
	if (loading_indicator.length > 0) {
		loading_indicator.remove();
	}
	searchbar.append("<div class='search-loading-indicator'></div>");

	fetch(`ajax-functions/player-overview-card-ajax.php`, {
		method: "GET",
		signal: player_search_controller.signal,
		headers: {
			search: input_value,
		}
	})
		.then(res => res.text())
		.then(cards => {
			$('.search-loading-indicator').remove();
			recents_list.css("display","none");
			player_list.html(cards);
		})
		.catch(error => {
			if (error.name === "AbortError") {
				console.log(error)
			} else {
				console.error(error)
			}
		});
}
async function reload_recent_players(initial=false) {
	let player_list = $('.recent-players-list');
	let recents = localStorage.getItem("searched_players_PUUIDS");
	if (JSON.parse(recents) == null || JSON.parse(recents).length === 0) {
		player_list.html("");
		return;
	}

	fetch(`ajax-functions/player-overview-card-ajax.php`, {
		method: "GET",
		headers: {
			"puuids": localStorage.getItem("searched_players_PUUIDS"),
		},
	})
		.then(res => res.text())
		.then(async player_cards => {
			$('.search-loading-indicator').remove();
			if (initial) {
				player_list.hide();
			}
			player_list.html(`<span>${get_material_icon("history")}Zuletzt gesucht:</span>${player_cards}`);
			if (initial) {
				player_list.fadeIn(200);
			}
		})
		.catch(error => console.error(error))
}
function remove_recent_player(puuid) {
	event.preventDefault();
	let recents = JSON.parse(localStorage.getItem("searched_players_PUUIDS"));
	if (recents === null) {
		return;
	}
	let index = recents.indexOf(puuid);
	recents.splice(index,1);
	localStorage.setItem("searched_players_PUUIDS",JSON.stringify(recents));
	if ($("body.players").length > 0) {
		reload_recent_players();
	}
}
$(document).ready(function () {
	if ($("body.players").length === 0) {
		return;
	}
	$('body.players .searchbar input').on("input",search_players);
	let player_search_input = $("input.search-players")[0].value;
	if (player_search_input != null && player_search_input.length > 2) {
		search_players();
	} else {
		reload_recent_players(true);
	}
});

// player history popup
let current_player_in_popup = null;
async function popup_player(PUUID, add_to_recents = false) {
	event.preventDefault();
	let popup = $('.player-popup');

	if (popup.length === 0) {
		$("header").after(`<div class="player-popup-bg" onclick="close_popup_player(event)"><div class="player-popup"></div></div>`)
	}
	popup = $('.player-popup');
	let popupbg = $('.player-popup-bg');

	let pagebody = $("body");

	if (add_to_recents) {
		let recents = JSON.parse(localStorage.getItem("searched_players_PUUIDS"));
		if (recents === null) {
			recents = [PUUID];
		}
		if (recents.includes(PUUID)) {
			let index = recents.indexOf(PUUID);
			recents.splice(index,1);
		}
		recents.unshift(PUUID);
		while (recents.length > 5) {
			recents = recents.slice(0,5);
		}
		localStorage.setItem("searched_players_PUUIDS",JSON.stringify(recents));
		if ($("body.players").length > 0) {
			reload_recent_players();
		}
	}

	if (current_player_in_popup === PUUID) {
		popupbg.css("opacity","0");
		popupbg.css("display","block");
		await new Promise(r => setTimeout(r, 10));
		popupbg.css("opacity","1");
		pagebody.addClass("popup_open");
		return;
	}

	current_player_in_popup = PUUID;
	popup.empty();

	popup.append(`<div class='close-button' onclick='closex_popup_player()'>${get_material_icon("close")}</div>`);
	popup.append("<div class='close-button-space'><div class='popup-loading-indicator'></div></div>");

	popupbg.css("opacity","0");
	popupbg.css("display","block");
	await new Promise(r => setTimeout(r, 10));
	popupbg.css("opacity","1");
	pagebody.addClass("popup_open");

	if (PUUID === "") {
		popup.append("Für diesen Spieler wurden keine weiteren Profile gefunden")
	}

	fetch(`ajax-functions/player-overview-ajax.php`, {
		method: "GET",
		headers: {
			puuid: PUUID,
		}
	})
		.then(res => res.text())
		.then(async content => {
			if (current_player_in_popup === PUUID) {
				popup.append(content);
				let popup_loader = $('.popup-loading-indicator');
				popup_loader.css("opacity","0");
				await new Promise(r => setTimeout(r, 210));
				popup_loader.remove();
			}
		})
		.catch(error => console.error(error));
}
async function close_popup_player(event) {
	let popupbg = $('.player-popup-bg');
	if (event.target === popupbg[0]) {
		popupbg.css("opacity","0");
		await new Promise(r => setTimeout(r, 250));
		$("body").removeClass("popup_open")
		popupbg.css("display","none");
	}
}
async function closex_popup_player() {
	let popupbg = $('.player-popup-bg');
	popupbg.css("opacity","0");
	await new Promise(r => setTimeout(r, 250));
	$("body").removeClass("popup_open")
	popupbg.css("display","none");
}
$(document).ready(function () {
	let body = $('body');
	if (body.hasClass("players")) {
		window.addEventListener("keydown", (event) => {
			if (event.key === "Escape") {
				closex_popup_player();
			}
		})
	}
});

function expand_playercard(card_button) {
	event.preventDefault();
	let card = card_button.parentNode;
	if (card.classList.contains("expanded-pcard")) {
		card.classList.remove("expanded-pcard");
	} else {
		card.classList.add("expanded-pcard");
	}
}
function expand_all_playercards(collapse=false) {
	event.preventDefault();
	let cards = document.getElementsByClassName("player-card");
	for (const card of cards) {
		if (collapse) {
			card.classList.remove("expanded-pcard");
		} else {
			card.classList.add("expanded-pcard");
		}
	}
}

// TODO: check code above (player-overview)

// user update
function format_time_minsec(date) {
	let format, trenner = "", min = "", nullausgleich = "";
	if (date.getMinutes() === 0) {
		format = " Sekunden";
	} else {
		min = date.getMinutes();
		format = " Minuten";
		trenner = ":";
		if (date.getSeconds() < 10) {
			nullausgleich = "0";
		}
	}
	return min + trenner + nullausgleich + date.getSeconds() + format;
}

let user_update_running = false;
window.onbeforeunload =  function() {
	if (user_update_running) {
		return "Ein Update läuft noch, beim verlassen der Seite wird das Update nicht abgeschlossen. Sicher verlassen?"
	}
}
async function user_update_group(button) {
	let group_ID = button.getAttribute("data-group");
	$(button).addClass("user_updating");
	button.disabled = true;
	user_update_running = true;

	let loading_width = 0;

	let last_update;

	await fetch(`ajax/get-data.php`, {
		method: "GET",
		headers: {
			type: "last-update-time",
			updatetype: "group",
			itemid: group_ID,
		}
	})
		.then(res => res.text())
		.then(time => {
			last_update = Date.parse(time);
		})
		.catch(error => console.error(error));

	let current = Date.now();
	let diff = new Date(current - last_update);

	if (current - last_update < 6000) {
		let rest = new Date(600000 - (current - last_update));
		window.alert(`Das letzte Update wurde vor ${format_time_minsec(diff)} durchgeführt. Versuche es in ${format_time_minsec(rest)} noch einmal`);
		await new Promise(r => setTimeout(r, 1000));
		$(button).removeClass("user_updating");
		button.disabled = false;
		user_update_running = false;
		return;
	}

	await fetch(`ajax/user-update-functions.php`, {
		method: "POST",
		headers: {
			type: "update_start_time",
			updatetype: "group",
			itemid: group_ID,
		}
	})
		.then(() => $("div.updatebuttonwrapper span").html("letztes Update:<br>vor ein paar Sekunden"))
		.catch(e => console.error(e));

	loading_width = 1;
	button.style.setProperty("--update-loading-bar-width", `${loading_width}%`);

	await fetch(`ajax/user-update-functions.php`, {
		method: "GET",
		headers: {
			type: "teams_from_group",
			groupid: group_ID,
		}
	})
		.catch(e => console.error(e));

	await new Promise(r => setTimeout(r, 1000));

	loading_width = 20;
	button.style.setProperty("--update-loading-bar-width", `${loading_width}%`);

	await fetch(`ajax/user-update-functions.php`, {
		method: "GET",
		headers: {
			type: "matchups_from_group",
			groupid: group_ID,
		}
	})
		.catch(e => console.error(e));

	await new Promise(r => setTimeout(r, 1000));

	loading_width = 40;
	button.style.setProperty("--update-loading-bar-width", `${loading_width}%`);

	await fetch(`ajax/get-data.php`, {
		method: "GET",
		headers: {
			type: "matchups",
			tournamentID: group_ID,
			idonly: "true",
		}
	})
		.then(res => res.json())
		.then(async matchids => {
			for (const match of matchids) {
				await fetch(`ajax/user-update-functions.php`, {
					method: "GET",
					headers: {
						type: "matchresult",
						matchid: match,
					}
				})
					.then(() => {
						loading_width = loading_width + 50/matchids.length;
						button.style.setProperty("--update-loading-bar-width", `${loading_width}%`);
					})
					.catch(e => console.error(e));
				await new Promise(r => setTimeout(r, 1000));
			}
		})
		.catch(error => console.error(error));

	loading_width = 90;
	button.style.setProperty("--update-loading-bar-width", `${loading_width}%`);

	await fetch(`./admin/ajax/get-opl-data.php`, {
		method: "GET",
		headers: {
			"type": "calculate_standings_from_matchups",
			"id": group_ID,
		}
	})
		.catch(e => console.error(e));

	loading_width = 100;
	button.style.setProperty("--update-loading-bar-width", `${loading_width}%`);

	$(button).removeClass("user_updating");
	button.disabled = false;
	user_update_running = false;
	loading_width = 0;
	button.style.setProperty("--update-loading-bar-width", "0");

	fetch(`ajax/create-page-elements.php`, {
		method: "GET",
		headers: {
			type: "standings",
			groupid: group_ID,
		}
	})
		.then(res => res.text())
		.then(standings => {
			$("div.standings").replaceWith(standings);
		})
		.catch(error => console.error(error));

	let matchbuttons = $("div.match-button-wrapper");
	for (const matchbutton of matchbuttons) {
		let match_ID = matchbutton.getAttribute("data-matchid");
		let matchtype = matchbutton.getAttribute("data-matchtype")

		fetch(`ajax/create-page-elements.php`, {
			method: "GET",
			headers: {
				type: "matchbutton",
				matchid: match_ID,
				matchtype: matchtype,
			}
		})
			.then(res => res.text())
			.then(new_matchbutton => {
				$(matchbutton).replaceWith(new_matchbutton);
			})
			.catch(error => console.error(error));
	}
}
$(document).ready(function () {
	$(".user_update_group").on("click", function () {
		user_update_group(this);
	});
});

async function user_update_team(button) {
	let team_ID = button.getAttribute("data-team");
	let tournamentID = button.getAttribute("data-tournament");
	let groupID = button.getAttribute("data-group");
	$(button).addClass("user_updating");
	button.disabled = true;
	user_update_running = true;

	let loading_width = 0;

	let last_update;

	await fetch(`ajax/get-data.php`, {
		method: "GET",
		headers: {
			type: "last-update-time",
			updatetype: "team",
			itemid: team_ID,
		}
	})
		.then(res => res.text())
		.then(time => {
			last_update = Date.parse(time);
		})
		.catch(e => console.error(e));

	let current = Date.now();
	let diff = new Date(current - last_update);

	if (current - last_update < 6000) {
		let rest = new Date(600000 - (current - last_update));
		window.alert(`Das letzte Update wurde vor ${format_time_minsec(diff)} durchgeführt. Versuche es in ${format_time_minsec(rest)} noch einmal`);
		await new Promise(r => setTimeout(r, 1000));
		$(button).removeClass("user_updating");
		button.disabled = false;
		user_update_running = false;
		return;
	}

	await fetch(`ajax/user-update-functions.php`, {
		method: "POST",
		headers: {
			type: "update_start_time",
			updatetype: "team",
			itemid: team_ID,
		}
	})
		.then(() => $("div.updatebuttonwrapper span").html("letztes Update:<br>vor ein paar Sekunden"))
		.catch(e => console.error(e));

	loading_width = 1;
	button.style.setProperty("--update-loading-bar-width", `${loading_width}%`);

	await fetch(`ajax/user-update-functions.php`, {
		method: "GET",
		headers: {
			type: "players_in_team",
			teamid: team_ID,
			tournamentid: tournamentID,
		}
	})
		.catch(e => console.error(e));

	loading_width = 10;
	button.style.setProperty("--update-loading-bar-width", `${loading_width}%`);

	await fetch(`ajax/get-data.php`, {
		method: "GET",
		headers: {
			type: "players",
			teamid: team_ID,
			tournamentid: tournamentID,
		}
	})
		.then(res => res.json())
		.then(async players => {
			for (const player of players) {
				await fetch(`ajax/user-update-functions.php`, {
					method: "GET",
					headers: {
						type: "summoner_for_player",
						playerid: player.OPL_ID,
					}
				})
					.then(() => {
						loading_width = loading_width + 15/players.length;
						button.style.setProperty("--update-loading-bar-width", `${loading_width}%`);
					})
					.catch(e => console.error(e));
				await new Promise(r => setTimeout(r, 1000));
			}
		})
		.catch(e => console.error(e));

	loading_width = 25;
	button.style.setProperty("--update-loading-bar-width", `${loading_width}%`);

	await fetch(`admin/ajax/get-rgapi-data.php`, {
		method: "GET",
		headers: {
			type: "puuids-by-team",
			team: team_ID,
		}
	})
		.catch(e => console.error(e));

	await new Promise(r => setTimeout(r, 1000));

	loading_width = 30;
	button.style.setProperty("--update-loading-bar-width", `${loading_width}%`);

	await fetch(`ajax/user-update-functions.php`, {
		method: "GET",
		headers: {
			type: "recalc_team_stats",
			teamid: team_ID,
			tournamentID: tournamentID,
		}
	})
		.catch(e => console.error(e));

	loading_width = 35;
	button.style.setProperty("--update-loading-bar-width", `${loading_width}%`);

	await fetch(`ajax/user-update-functions.php`, {
		method: "GET",
		headers: {
			type: "teams_from_group",
			groupID: groupID,
		}
	})
		.catch(e => console.error(e));

	await new Promise(r => setTimeout(r, 1000));

	loading_width = 45;
	button.style.setProperty("--update-loading-bar-width", `${loading_width}%`);

	await fetch(`ajax/user-update-functions.php`, {
		method: "GET",
		headers: {
			type: "matches_from_group",
			groupid: groupID,
		}
	})
		.catch(e => console.error(e));

	await new Promise(r => setTimeout(r, 1000));

	loading_width = 70;
	button.style.setProperty("--update-loading-bar-width", `${loading_width}%`);

	await fetch(`ajax/get-data.php`, {
		method: "GET",
		headers: {
			type: "matchups",
			tournamentID: groupID,
			teamid: team_ID,
			idonly: "true",
		}
	})
		.then(res => res.json())
		.then(async matchids => {
			for (const match of matchids) {
				await fetch(`ajax/user-update-functions.php`, {
					method: "GET",
					headers: {
						type: "matchresult",
						matchid: match,
					}
				})
					.then(() => {
						loading_width = loading_width + 25/matchids.length;
						button.style.setProperty("--update-loading-bar-width", `${loading_width}%`);
					})
					.catch(e => console.error(e));
				await new Promise(r => setTimeout(r, 1000));
			}
		})
		.catch(e => console.error(e));

	loading_width = 95;
	button.style.setProperty("--update-loading-bar-width", `${loading_width}%`);

	await fetch(`./admin/ajax/get-opl-data.php`, {
		method: "GET",
		headers: {
			"type": "calculate_standings_from_matchups",
			"id": groupID,
		}
	})
		.catch(e => console.error(e));

	loading_width = 100;
	button.style.setProperty("--update-loading-bar-width", `${loading_width}%`);

	$(button).removeClass("user_updating");
	button.disabled = false;
	user_update_running = false;
	loading_width = 0;
	button.style.setProperty("--update-loading-bar-width", "0");

	fetch(`ajax/create-page-elements.php`, {
		method: "GET",
		headers: {
			type: "summoner-card-container",
			teamid: team_ID,
			tournamentID: tournamentID,
		}
	})
		.then(res => res.text())
		.then(summonercards => {
			$("div.summoner-card-container").replaceWith(summonercards);
		})
		.catch(e => console.error(e));

	fetch(`ajax/get-data.php`, {
		method: "GET",
		headers: {
			type: "players",
			teamid: team_ID,
			tournamentid: tournamentID,
			summonersonly: "true",
		}
	})
		.then(res => res.json())
		.then(players => {
			$(".title .op-gg .player-amount").replaceWith(`(${players.length} Spieler)`);
			let summoners = "";
			players.forEach((player,index) => {
				if (index === 0) {
					summoners = player
				} else {
					summoners = summoners.concat(",",encodeURIComponent(player));
				}
			});
			$(".title .op-gg").attr("href",`https://www.op.gg/multisearch/euw?summoners=${summoners}`);
		})
		.catch(e => console.error(e));

	fetch(`ajax/create-page-elements.php`, {
		method: "GET",
		headers: {
			type: "standings",
			groupID: groupID,
			teamid: team_ID,
		}
	})
		.then(res => res.text())
		.then(standings => {
			$("div.standings").replaceWith(standings);
		})
		.catch(e => console.error(e));

	let matchbuttons = $("div.match-button-wrapper");
	for (const matchbutton of matchbuttons) {
		let match_ID = matchbutton.getAttribute("data-matchid");
		let matchtype = matchbutton.getAttribute("data-matchtype");
		fetch(`ajax/create-page-elements.php`, {
			method: "GET",
			headers: {
				type: "matchbutton",
				matchid: match_ID,
				matchtype: matchtype,
				teamid: team_ID,
			}
		})
			.then(res => res.text())
			.then(new_matchbutton => {
				$(matchbutton).replaceWith(new_matchbutton);
			})
			.catch(e => console.error(e));
	}

}
$(document).ready(function () {
	$(".user_update_team").on("click", function () {
		user_update_team(this);
	});
});

async function user_update_match(button) {
	let match_ID = button.getAttribute("data-match");
	let format = button.getAttribute("data-matchformat");
	let team_ID = button.getAttribute("data-team");
	$(button).addClass("user_updating");
	button.disabled = true;
	user_update_running = true;

	let loading_width = 0;

	let last_update;

	await fetch(`ajax/get-data.php`, {
		method: "GET",
		headers: {
			type: "last-update-time",
			updatetype: "match",
			itemID: match_ID,
		}
	})
		.then(res => res.text())
		.then(async time => {
			last_update = Date.parse(time);
		})
		.catch(e => console.error(e));

	let current = Date.now();
	let diff = new Date(current - last_update);

	if (current - last_update < 6000) {
		let rest = new Date(600000 - (current - last_update));
		window.alert(`Das letzte Update wurde vor ${format_time_minsec(diff)} durchgeführt. Versuche es in ${format_time_minsec(rest)} noch einmal`);
		await new Promise(r => setTimeout(r, 1000));
		$(button).removeClass("user_updating");
		button.disabled = false;
		user_update_running = false;
		return;
	}

	await fetch(`ajax/user-update-functions.php`, {
		method: "POST",
		headers: {
			type: "update_start_time",
			updatetype: "match",
			itemID: match_ID,
		}
	})
		.then(() => $("div.updatebuttonwrapper span").html("letztes Update:<br>vor ein paar Sekunden"))
		.catch(e => console.error(e));

	loading_width = 1;
	button.style.setProperty("--update-loading-bar-width", `${loading_width}%`);

	await fetch(`ajax/user-update-functions.php`, {
		method: "GET",
		headers: {
			type: "matchresult",
			matchID: match_ID,
		}
	})
		.catch(e => console.error(e));

	loading_width = 20;
	button.style.setProperty("--update-loading-bar-width", `${loading_width}%`);

	let tournamentID;

	await fetch(`ajax/get-data.php`, {
		method: "GET",
		headers: {
			type: "matchup",
			matchid: match_ID,
			returntournamentid: "true",
		}
	})
		.then(res => res.text())
		.then(id => tournamentID = id)
		.catch(e => console.error(e));

	await fetch(`ajax/get-data.php`, {
		method: "GET",
		headers: {
			type: "players-in-match",
			matchid: match_ID,
			cut_players: "true",
			idonly: "true",
		}
	})
		.then(res => res.json())
		.then(async playerids => {
			for (const playerid of playerids) {
				await fetch(`admin/ajax/get-rgapi-data.php`, {
					method: "GET",
					headers: {
						type: "games-by-player",
						playerID: playerid,
						tournamentID: tournamentID,
					}
				})
					.then(() => {
						loading_width = loading_width + 30/playerids.length;
						button.style.setProperty("--update-loading-bar-width", `${loading_width}%`);
					})
					.catch(e => console.error(e));
				await new Promise(r => setTimeout(r, 100));
			}
		})
		.catch(e => console.error(e));

	loading_width = 50;
	button.style.setProperty("--update-loading-bar-width", `${loading_width}%`);

	await fetch(`ajax/get-data.php`, {
		method: "GET",
		headers: {
			type: "games-from-players-in-match",
			matchid: match_ID,
			withPUUIDonly: "true",
		}
	})
		.then(res => res.json())
		.then(async gameids => {
			for (const gameid of gameids) {
				await fetch(`admin/ajax/get-rgapi-data.php`, {
					method: "GET",
					headers: {
						type: "matchdata-and-assign",
						matchID: gameid,
						tournamentID: tournamentID,
					}
				})
					.then(() => {
						loading_width = loading_width + 35/gameids.length;
						button.style.setProperty("--update-loading-bar-width", `${loading_width}%`);
					})
					.catch(e => console.error(e));
				await new Promise(r => setTimeout(r, 100));
			}
		})
		.catch(e => console.error(e));

	loading_width = 90;
	button.style.setProperty("--update-loading-bar-width", `${loading_width}%`);

	await fetch(`ajax/user-update-functions.php`, {
		method: "GET",
		headers: {
			type: "recalc_team_stats",
			teamID: team_ID,
			tournamentID: tournamentID,
		}
	})
		.catch(e => console.error(e));

	loading_width = 100;
	button.style.setProperty("--update-loading-bar-width", `${loading_width}%`);

	$(button).removeClass("user_updating");
	button.disabled = false;
	user_update_running = false;
	loading_width = 0;
	button.style.setProperty("--update-loading-bar-width", "0");


	await fetch(`ajax/get-data.php`, {
		method: "GET",
		headers: {
			type: "match-games-teams-by-matchid",
			matchID: match_ID,
		}
	})
		.then(res => res.json())
		.then(data => {
			let games = data['games'];
			let popup = $('.mh-popup');

			if (games.length > 0) {
				$(".no-game-found").remove();
				$(".game").remove();
			}
			let game_counter = 0;
			for (const [i, game] of games.entries()) {
				if (current_match_in_popup === parseInt(match_ID)) {
					popup.append(`<div class='game game${i}'></div>`);
				}
				let gameID = game['RIOT_matchID'];

				let fetchheaders = new Headers({
					gameid: gameID
				});
				if (team_ID !== null) {
					fetchheaders.append("teamid", team_ID)
				}
				fetch(`ajax/game.php`, {
					method: "GET",
					headers: fetchheaders,
				})
					.then(res => res.text())
					.then(data => {
						let game_wrap = popup.find('.game' + i);
						if (current_match_in_popup === parseInt(match_ID)) {
							game_wrap.empty();
							game_wrap.append(data);
							game_counter++;
						}
					})
					.catch(e => console.error(e));
			}
		})
		.catch(e => console.error(e));
}
$(document).ready(function () {
	$(".user_update_match").on("click", function () {
		user_update_match(this);
	});
});

// allgemeine Helper
function get_material_icon(name,nowrap=false) {
	let res = "";
	if (!nowrap) res = "<div class='material-symbol'>";
	if (name === "close") res += "<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"48\" viewBox=\"0 96 960 960\" width=\"48\"><path d=\"M480 618 270 828q-9 9-21 9t-21-9q-9-9-9-21t9-21l210-210-210-210q-9-9-9-21t9-21q9-9 21-9t21 9l210 210 210-210q9-9 21-9t21 9q9 9 9 21t-9 21L522 576l210 210q9 9 9 21t-9 21q-9 9-21 9t-21-9L480 618Z\"/></svg>";
	if (name === "history") res += "<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"48\" viewBox=\"0 -960 960 960\" width=\"48\"><path d=\"M477-120q-142 0-243.5-95.5T121-451q-1-12 7.5-21t21.5-9q12 0 20.5 8.5T181-451q11 115 95 193t201 78q127 0 215-89t88-216q0-124-89-209.5T477-780q-68 0-127.5 31T246-667h75q13 0 21.5 8.5T351-637q0 13-8.5 21.5T321-607H172q-13 0-21.5-8.5T142-637v-148q0-13 8.5-21.5T172-815q13 0 21.5 8.5T202-785v76q52-61 123.5-96T477-840q75 0 141 28t115.5 76.5Q783-687 811.5-622T840-482q0 75-28.5 141t-78 115Q684-177 618-148.5T477-120Zm34-374 115 113q9 9 9 21.5t-9 21.5q-9 9-21 9t-21-9L460-460q-5-5-7-10.5t-2-11.5v-171q0-13 8.5-21.5T481-683q13 0 21.5 8.5T511-653v159Z\"/></svg>";
	if (name === "sync") res += "<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"48\" viewBox=\"0 -960 960 960\" width=\"48\"><path d=\"M220-477q0 63 23.5 109.5T307-287l30 21v-94q0-13 8.5-21.5T367-390q13 0 21.5 8.5T397-360v170q0 13-8.5 21.5T367-160H197q-13 0-21.5-8.5T167-190q0-13 8.5-21.5T197-220h100l-15-12q-64-51-93-111t-29-134q0-94 49.5-171.5T342-766q11-5 21 0t14 16q5 11 0 22.5T361-710q-64 34-102.5 96.5T220-477Zm520-6q0-48-23.5-97.5T655-668l-29-26v94q0 13-8.5 21.5T596-570q-13 0-21.5-8.5T566-600v-170q0-13 8.5-21.5T596-800h170q13 0 21.5 8.5T796-770q0 13-8.5 21.5T766-740H665l15 14q60 56 90 120t30 123q0 93-48 169.5T623-195q-11 6-22.5 1.5T584-210q-5-11 0-22.5t16-17.5q65-33 102.5-96T740-483Z\"/></svg>";
	if (name === "manage_search") res += "<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"48\" viewBox=\"0 96 960 960\" width=\"48\"><path d=\"M110 436q-12.75 0-21.375-8.675Q80 418.649 80 405.825 80 393 88.625 384.5T110 376h140q12.75 0 21.375 8.675 8.625 8.676 8.625 21.5 0 12.825-8.625 21.325T250 436H110Zm0 210q-12.75 0-21.375-8.675Q80 628.649 80 615.825 80 603 88.625 594.5T110 586h140q12.75 0 21.375 8.675 8.625 8.676 8.625 21.5 0 12.825-8.625 21.325T250 646H110Zm707 189L678 696q-26 20-56 30t-62 10q-83 0-141.5-58.5T360 536q0-83 58.5-141.5T560 336q83 0 141.5 58.5T760 536q0 32-10 62t-30 56l139 139q9 9 9 21t-9 21q-9 9-21 9t-21-9ZM559.765 676Q618 676 659 635.235q41-40.764 41-99Q700 478 659.235 437q-40.764-41-99-41Q502 396 461 436.765q-41 40.764-41 99Q420 594 460.765 635q40.764 41 99 41ZM110 856q-12.75 0-21.375-8.675Q80 838.649 80 825.825 80 813 88.625 804.5T110 796h340q12.75 0 21.375 8.675 8.625 8.676 8.625 21.5 0 12.825-8.625 21.325T450 856H110Z\"/></svg>";
	if (name === "info") res += "<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"48\" viewBox=\"0 96 960 960\" width=\"48\"><path d=\"M483.175 776q12.825 0 21.325-8.625T513 746V566q0-12.75-8.675-21.375-8.676-8.625-21.5-8.625-12.825 0-21.325 8.625T453 566v180q0 12.75 8.675 21.375 8.676 8.625 21.5 8.625Zm-3.193-314q14.018 0 23.518-9.2T513 430q0-14.45-9.482-24.225-9.483-9.775-23.5-9.775-14.018 0-23.518 9.775T447 430q0 13.6 9.482 22.8 9.483 9.2 23.5 9.2Zm.284 514q-82.734 0-155.5-31.5t-127.266-86q-54.5-54.5-86-127.341Q80 658.319 80 575.5q0-82.819 31.5-155.659Q143 347 197.5 293t127.341-85.5Q397.681 176 480.5 176q82.819 0 155.659 31.5Q709 239 763 293t85.5 127Q880 493 880 575.734q0 82.734-31.5 155.5T763 858.316q-54 54.316-127 86Q563 976 480.266 976Zm.234-60Q622 916 721 816.5t99-241Q820 434 721.188 335 622.375 236 480 236q-141 0-240.5 98.812Q140 433.625 140 576q0 141 99.5 240.5t241 99.5Zm-.5-340Z\"/></svg>";
	if (name === "open_in_new") res += "<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"48\" viewBox=\"0 96 960 960\" width=\"48\"><path d=\"M180 936q-24 0-42-18t-18-42V276q0-24 18-42t42-18h249q12.75 0 21.375 8.675 8.625 8.676 8.625 21.5 0 12.825-8.625 21.325T429 276H180v600h600V627q0-12.75 8.675-21.375 8.676-8.625 21.5-8.625 12.825 0 21.325 8.625T840 627v249q0 24-18 42t-42 18H180Zm181.13-241.391Q353 686 352.5 674q-.5-12 8.5-21l377-377H549q-12.75 0-21.375-8.675-8.625-8.676-8.625-21.5 0-12.825 8.625-21.325T549 216h261q12.75 0 21.375 8.625T840 246v261q0 12.75-8.675 21.375-8.676 8.625-21.5 8.625-12.825 0-21.325-8.625T780 507V319L403 696q-8.442 8-20.721 8t-21.149-9.391Z\"/></svg>";
	if (name === "check_indeterminate_small") res += "<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"48\" viewBox=\"0 96 960 960\" width=\"48\"><path d=\"M310 606q-12.75 0-21.375-8.675-8.625-8.676-8.625-21.5 0-12.825 8.625-21.325T310 546h340q12.75 0 21.375 8.675 8.625 8.676 8.625 21.5 0 12.825-8.625 21.325T650 606H310Z\"/></svg>";
	if (name === "expand_less") res += "<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"48\" viewBox=\"0 96 960 960\" width=\"48\"><path d=\"M262 689q-9-9-9.5-21t8.5-21l198-198q5-5 10-7t11-2q6 0 11 2t10 7l198 197q9 8 9 20.5t-9 21.5q-9 9-21.5 9t-21.5-9L480 513 304 690q-8 9-20.5 8.5T262 689Z\"/></svg>";
	if (name === "expand_more") res += "<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"48\" viewBox=\"0 96 960 960\" width=\"48\"><path d=\"M480 699q-6 0-11-2t-10-7L261 492q-8-8-7.5-21.5T262 449q10-10 21.5-8.5T304 450l176 176 176-176q8-8 21.5-9t21.5 9q10 8 8.5 21t-9.5 22L501 690q-5 5-10 7t-11 2Z\"/></svg>";
	if (name === "dark_mode") res += "<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"48\" viewBox=\"0 96 960 960\" width=\"48\"><path d=\"M480 936q-150 0-255-105T120 576q0-135 79.5-229T408 226q41-8 56 14t-1 60q-9 23-14 47t-5 49q0 90 63 153t153 63q25 0 48.5-4.5T754 595q43-16 64 1.5t11 59.5q-27 121-121 200.5T480 936Zm0-60q109 0 190-67.5T771 650q-25 11-53.667 16.5Q688.667 672 660 672q-114.689 0-195.345-80.655Q384 510.689 384 396q0-24 5-51.5t18-62.5q-98 27-162.5 109.5T180 576q0 125 87.5 212.5T480 876Zm-4-297Z\"/></svg>";
	if (name === "light_mode") res += "<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"48\" viewBox=\"0 96 960 960\" width=\"48\"><path d=\"M479.765 716Q538 716 579 675.235q41-40.764 41-99Q620 518 579.235 477q-40.764-41-99-41Q422 436 381 476.765q-41 40.764-41 99Q340 634 380.765 675q40.764 41 99 41Zm.235 60q-83 0-141.5-58.5T280 576q0-83 58.5-141.5T480 376q83 0 141.5 58.5T680 576q0 83-58.5 141.5T480 776ZM70 606q-12.75 0-21.375-8.675Q40 588.649 40 575.825 40 563 48.625 554.5T70 546h100q12.75 0 21.375 8.675 8.625 8.676 8.625 21.5 0 12.825-8.625 21.325T170 606H70Zm720 0q-12.75 0-21.375-8.675-8.625-8.676-8.625-21.5 0-12.825 8.625-21.325T790 546h100q12.75 0 21.375 8.675 8.625 8.676 8.625 21.5 0 12.825-8.625 21.325T890 606H790ZM479.825 296Q467 296 458.5 287.375T450 266V166q0-12.75 8.675-21.375 8.676-8.625 21.5-8.625 12.825 0 21.325 8.625T510 166v100q0 12.75-8.675 21.375-8.676 8.625-21.5 8.625Zm0 720q-12.825 0-21.325-8.62-8.5-8.63-8.5-21.38V886q0-12.75 8.675-21.375 8.676-8.625 21.5-8.625 12.825 0 21.325 8.625T510 886v100q0 12.75-8.675 21.38-8.676 8.62-21.5 8.62ZM240 378l-57-56q-9-9-8.629-21.603.37-12.604 8.526-21.5 8.896-8.897 21.5-8.897Q217 270 226 279l56 57q8 9 8 21t-8 20.5q-8 8.5-20.5 8.5t-21.5-8Zm494 495-56-57q-8-9-8-21.375T678.5 774q8.5-9 20.5-9t21 9l57 56q9 9 8.629 21.603-.37 12.604-8.526 21.5-8.896 8.897-21.5 8.897Q743 882 734 873Zm-56-495q-9-9-9-21t9-21l56-57q9-9 21.603-8.629 12.604.37 21.5 8.526 8.897 8.896 8.897 21.5Q786 313 777 322l-57 56q-8 8-20.364 8-12.363 0-21.636-8ZM182.897 873.103q-8.897-8.896-8.897-21.5Q174 839 183 830l57-56q8.8-9 20.9-9 12.1 0 20.709 9Q291 783 291 795t-9 21l-56 57q-9 9-21.603 8.629-12.604-.37-21.5-8.526ZM480 576Z\"/></svg>";
	if (name === "visibility") res += "<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"48\" viewBox=\"0 96 960 960\" width=\"48\"><path d=\"M480.118 726Q551 726 600.5 676.382q49.5-49.617 49.5-120.5Q650 485 600.382 435.5q-49.617-49.5-120.5-49.5Q409 386 359.5 435.618q-49.5 49.617-49.5 120.5Q310 627 359.618 676.5q49.617 49.5 120.5 49.5Zm-.353-58Q433 668 400.5 635.265q-32.5-32.736-32.5-79.5Q368 509 400.735 476.5q32.736-32.5 79.5-32.5Q527 444 559.5 476.735q32.5 32.736 32.5 79.5Q592 603 559.265 635.5q-32.736 32.5-79.5 32.5ZM480 856q-138 0-251.5-75T53.145 582.923Q50 578 48.5 570.826 47 563.652 47 556t1.5-14.826Q50 534 53.145 529.077 115 406 228.5 331T480 256q138 0 251.5 75t175.355 198.077Q910 534 911.5 541.174 913 548.348 913 556t-1.5 14.826q-1.5 7.174-4.645 12.097Q845 706 731.5 781T480 856Zm0-300Zm-.169 240Q601 796 702.5 730.5 804 665 857 556q-53-109-154.331-174.5-101.332-65.5-222.5-65.5Q359 316 257.5 381.5 156 447 102 556q54 109 155.331 174.5 101.332 65.5 222.5 65.5Z\"/></svg>";
	if (name === "visibility_off") res += "<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"48\" viewBox=\"0 96 960 960\" width=\"48\"><path d=\"m629 637-44-44q26-71-27-118t-115-24l-44-44q17-11 38-16t43-5q71 0 120.5 49.5T650 556q0 22-5.5 43.5T629 637Zm129 129-40-40q49-36 85.5-80.5T857 556q-50-111-150-175.5T490 316q-42 0-86 8t-69 19l-46-47q35-16 89.5-28T485 256q135 0 249 74t174 199q3 5 4 12t1 15q0 8-1 15.5t-4 12.5q-26 55-64 101t-86 81Zm36 204L648 827q-35 14-79 21.5t-89 7.5q-138 0-253-74T52 583q-3-6-4-12.5T47 556q0-8 1.5-15.5T52 528q21-45 53.5-87.5T182 360L77 255q-9-9-9-21t9-21q9-9 21.5-9t21.5 9l716 716q8 8 8 19.5t-8 20.5q-8 10-20.5 10t-21.5-9ZM223 402q-37 27-71.5 71T102 556q51 111 153.5 175.5T488 796q33 0 65-4t48-12l-64-64q-11 5-27 7.5t-30 2.5q-70 0-120-49t-50-121q0-15 2.5-30t7.5-27l-97-97Zm305 142Zm-116 58Z\"/></svg>";
	if (name === "unfold_less") res += "<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"48\" viewBox=\"0 96 960 960\" width=\"48\"><path d=\"M480 467q-5 0-10.5-2t-10.5-7L322 321q-9-9-9-22t9-22q9-9 21-9t21 9l116 116 116-116q9-9 21.5-9t21.5 9q9 9 9 21.5t-9 21.5L501 458q-5 5-10 7t-11 2ZM322 874q-9-9-9-21.5t9-21.5l137-137q5-5 10.5-7t10.5-2q6 0 11 2t10 7l138 138q9 9 9 21t-9 21q-9 9-22 9t-22-9L480 759 365 874q-9 9-21.5 9t-21.5-9Z\"/></svg>";
	if (name === "unfold_more") res += "<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"48\" viewBox=\"0 96 960 960\" width=\"48\"><path d=\"M322 422q-9-9-9-22t9-22l137-137q5-5 10-7t11-2q5 0 10.5 2t10.5 7l137 137q9 9 9 22t-9 22q-9 9-22 9t-22-9L480 308 366 422q-9 9-22 9t-22-9Zm158 502q-5 0-10.5-2t-10.5-7L322 778q-9-9-9-22t9-22q9-9 22-9t22 9l114 114 114-114q9-9 22-9t22 9q9 9 9 22t-9 22L501 915q-5 5-10 7t-11 2Z\"/></svg>";
	if (!nowrap) res += "</div>";
	return res;
}

function getCookie(cname) {
	let name = cname + "=";
	let decodedCookie = decodeURIComponent(document.cookie);
	let ca = decodedCookie.split(';');
	for(let i = 0; i <ca.length; i++) {
		let c = ca[i];
		while (c.charAt(0) === ' ') {
			c = c.substring(1);
		}
		if (c.indexOf(name) === 0) {
			return c.substring(name.length, c.length);
		}
	}
	return "";
}

function close_warningheader() {
	$(".warning-header").remove();
}