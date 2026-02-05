import fragmentLoader from "./fragmentLoader";
import {drawAllBracketLines} from "./components/brackets";
import get_material_icon from "./utils/materialIcons";
$(document).ready(() => {
	$(".settings-option.login").on("click", () => {event.preventDefault(); document.getElementById("login-dialog").showModal(); toggle_settings_menu(false); document.getElementById("keypass").focus();});
});

// allgemeine Funktionen der Seite
function toggle_settings_menu(to_state = null) {
	event.preventDefault();
	let settings = $('.settings-menu');
	let settings_icon = $('.settings-button.material-symbol');
	if (to_state == null) {
		to_state = !settings.hasClass("shown");
	}
	if (to_state) {
		settings.addClass("shown");
		settings_icon.addClass("flipy");
	} else {
		settings.removeClass("shown")
		settings_icon.removeClass("flipy");
	}
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
		// switch to darkmode
		body.removeClass("light");
		await new Promise(r => setTimeout(r, 1));
		$('.settings-option.toggle-mode').html(get_material_icon("dark_mode"));
		$('img.color-switch').each(function() {
			let img = $(this);
			let logo_url = img.attr("src");
			$(this).attr('src',logo_url.replace("logo_light.webp","logo.webp"));
		});
		document.cookie = `lightmode=0; expires=${cookie_expiry}; path=/`;
	} else {
		// switch to lightmode
		body.addClass("light");
		await new Promise(r => setTimeout(r, 1));
		$('.settings-option.toggle-mode').html(get_material_icon("light_mode"));
		$('img.color-switch').each(function() {
			let img = $(this);
			let logo_url = img.attr("src");
			$(this).attr('src',logo_url.replace("logo.webp","logo_light.webp"));
		});
		document.cookie = `lightmode=1; expires=${cookie_expiry}; path=/`;
	}
	await new Promise(r => setTimeout(r, 10));
	document.getElementsByTagName("body")[0].style.transition = null;
	for (let i = 0; i < tag_num; i++) {
		tags[i].style.transition = null;
	}
}
$(document).ready(function () {
	$('header .settings-button').on("click",()=>{toggle_settings_menu()});
	$('header .settings-option.toggle-mode').on("click",toggle_darkmode);
	let settings = $('.settings-menu');
	let header = $('header');
	window.addEventListener("click", (event) => {
		if (settings.hasClass('shown')) {
			if (!$.contains(header.get(0),$(event.target).get(0)) && event.target !== header[0]) {
				toggle_settings_menu(false);
			}
		}
	});
	window.addEventListener("keydown", (event) => {
		if (event.key === "Escape" && $('header .settings-menu').hasClass('shown')) {
			toggle_settings_menu(false);
		}
	});
});
$(document).ready(function() {
	let encMail = "aW5mb0BzaWxlbmNlLmxvbA==";
	$(".settings-option.feedback").attr("href",`mailto:${atob(encMail)}`);
});

function clear_searchbar(event) {
	event.preventDefault();
	let searchbar = $(this.parentNode);
	searchbar.find("input[type=search]").val('').trigger('keyup').trigger('input').focus();
}
function toggle_clear_search_x(event) {
	let clear_button = $(this.parentNode).find(".search-clear");
	let input = $(this)[0].value;
	if (input === "") {
		clear_button.css("display", "none");
	} else {
		clear_button.css("display", "flex");
	}
}
$(document).ready(function() {
	$(".searchbar .search-clear").on("click", clear_searchbar);
	let searchbar = $(".searchbar input[type=search]");
	searchbar.on("input", toggle_clear_search_x);
	searchbar.on("mouseenter", toggle_clear_search_x);
});

// teamlist-filter
$(document).on('keyup', 'input.search-teams', () => search_teams());
$(document).on('change', '.div-select-wrap select.divisions', function () {
	filter_teams_list_division(this.value);
});
$(document).on('change', '.groups-select-wrap select.groups', function () {
	filter_teams_list_group(this.value);
});
function update_team_filter_groups(div_id) {
	if (div_id === "all") {
		$("select.groups").empty().append("<option value='all' selected='selected'>Alle Gruppen</option>");
	} else {
		fetch(`/api/tournaments/${div_id}/leafes`, {method: "GET"})
			.then(res => res.json())
			.then(groups => {
				let groupslist = $("select.groups");
				groupslist.empty().append("<option value='all' selected='selected'>Alle Gruppen</option>")
				for (let i = 0; i < groups.length; i++) {
					groupslist.append(`<option value='${groups[i]["id"]}'>Gruppe ${groups[i]["number"]}</option>`);
				}
			})
			.catch(error => console.error(error));
	}
}
function filter_teams_list_division(division) {
	update_team_filter_groups(division);
	let liste = document.getElementsByClassName("team-list")[0];
	let tags = liste.querySelectorAll('.team-button');
	let results = tags.length;
	for (let i = 0; i < tags.length; i++) {
		let values = tags[i].getAttribute("data-league");
		values = values.split(" ");
		if (division === "all" || values.includes(division)) {
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

	let div_selection = $(`select.divisions option[value='${division}']`).eq(0);
	if(div_selection.hasClass("standings_league")) {
		group_button.addClass('shown')
		let url = new URL(window.location.href);
		group_button.attr('href',`/turnier/${url.pathname.split("turnier/")[1].split("/")[0]}/gruppe/${division}`);
	} else {
		group_button.removeClass('shown')
	}


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
	let tags = liste.querySelectorAll('.team-button');
	let results = tags.length;
	for (let i = 0; i < tags.length; i++) {
		let values = tags[i].getAttribute("data-group");
		values = values.split(" ");
		if (group === "all" || values.includes(group)) {
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
		group_button.attr('href',`/turnier/${url.pathname.split("turnier/")[1].split("/")[0]}/gruppe/${group}`);
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
function search_teams() {
	const search_input = document.getElementsByClassName("search-teams")[0].value.toUpperCase();
	const liste = document.getElementsByClassName("team-list")[0];
	const team_buttons = liste.querySelectorAll('.team-button');
	let results = team_buttons.length;

	// Loop through all list items, and hide those who don't match the search query
	for (let i = 0; i < team_buttons.length; i++) {
		let txtValue = team_buttons[i].querySelectorAll(".team-name")[0].innerText;
		if (txtValue.toUpperCase().indexOf(search_input) > -1) {
			if (team_buttons[i].classList.contains("search-off")) {
				team_buttons[i].classList.remove("search-off");
			}
		} else {
			if (!(team_buttons[i].classList.contains("search-off"))) {
				team_buttons[i].classList.add("search-off");
			}
			results -= 1;
		}
	}
	if (results === 0) {
		document.getElementsByClassName(`no-search-res-text`)[0].style.display = "";
	} else {
		document.getElementsByClassName(`no-search-res-text`)[0].style.display = "none";
	}
}


// OPGG on Summoner Cards
$(document).on('click', '.summoner-card', function () {
	player_to_opgg_link(this.dataset.id, this.dataset.riotid)
});
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

function expand_collapse_game() {
	$(this).parent().parent().toggleClass("collapsed");
}
$(document).ready(function () {
	$(document).on("click", 'button.expand-game-details', expand_collapse_game);
});

// Elo Overview Swap Views
$(document).on('click', '.filter-button-wrapper button.filterb', function () {
	switch_elo_view(this.dataset.id, this.dataset.view);
});
$(document).on('click', '.settings-button-wrapper button.color-elo-list', () => color_elo_list());
function switch_elo_view(tournamentID,view) {
	event.preventDefault();
	let url = new URL(window.location.href);
	let area = $('.main-content');
	let but = $('.filter-button-wrapper button');
	let all_b = $('.filter-button-wrapper [data-view=all-teams]');
	let div_b = $('.filter-button-wrapper [data-view=div-teams]');
	let group_b = $('.filter-button-wrapper [data-view=group-teams]');
	let color_b = $('.settings-button-wrapper button span');
	let jump_b = $('.jump-button-wrapper');

    let stage = $(`button.elolist_switch_stage.active`).attr("data-stage");

	if (stage === "groups") {
		(view === "all-teams") ? jump_b.css("display","none") : jump_b.css("display","");
		group_b.css("display","");
	} else {
		jump_b.css("display","none");
		group_b.css("display","none");
	}

	but.removeClass('active');
	if (view === "all-teams" && stage === "groups") {
		all_b.addClass('active');
		url.searchParams.delete("view");
		url.searchParams.delete("stage");
		window.history.replaceState({}, '', url);
		add_elo_team_list(area,tournamentID,"all");
		color_b.text("Nach Liga einfärben");
	} else if (view === "div-teams" && stage === "groups") {
		div_b.addClass('active');
		url.searchParams.set("view","liga");
		url.searchParams.delete("stage");
		window.history.replaceState({}, '', url);
		add_elo_team_list(area,tournamentID,"div");
		color_b.text("Nach Rang einfärben");
	} else if (view === "group-teams" && stage === "groups") {
		group_b.addClass('active');
		url.searchParams.set("view","gruppe");
		url.searchParams.delete("stage");
		window.history.replaceState({}, '', url);
		add_elo_team_list(area,tournamentID,"group");
		color_b.text("Nach Rang einfärben");
	} else if (view === "all-teams" && stage === "wildcard") {
		all_b.addClass('active');
		url.searchParams.delete("view");
		url.searchParams.set("stage","wildcard");
		window.history.replaceState({}, '', url);
		add_elo_team_list(area,tournamentID,"all-wildcard");
		color_b.text("Nach Liga einfärben");
	} else if (view === "div-teams" && stage === "wildcard") {
        div_b.addClass('active');
        url.searchParams.set("view","liga");
        url.searchParams.set("stage","wildcard");
        window.history.replaceState({}, '', url);
        add_elo_team_list(area,tournamentID,"wildcard");
        color_b.text("Nach Rang einfärben");
    }
}
$(document).on('click', '.jump-button-wrapper button', function () {
	jump_to_league_elo(this.dataset.league);
})
function jump_to_league_elo(div_num) {
	event.preventDefault();
	let league = $(`.teams-elo-list h3.liga${div_num}`);
	$('html').stop().animate({scrollTop: league[0].offsetTop-40}, 300, 'swing');
}

let elo_list_fetch_control = null;
function add_elo_team_list(area,tournamentID,view="all") {
	if ($('.content-loading-indicator').length === 0) $('body').append("<div class='content-loading-indicator'></div>");

	if (elo_list_fetch_control !== null) elo_list_fetch_control.abort();
	elo_list_fetch_control = new AbortController();

	fragmentLoader(`elo-lists?tournamentId=${tournamentID}&view=${view}`, elo_list_fetch_control.signal)
		.then(list => {
			area.empty();
			area.append(list);
			$('.content-loading-indicator').remove();
		})
		.catch(error => {
			$('.content-loading-indicator').remove();
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

$(document).on('input', '.search-teams-elo', () => search_teams_elo());
let acCurrentFocus = 0;
function search_teams_elo() {
	let searchbar = $('body.elo-overview main .searchbar');
	let input = $('.search-teams-elo')[0];
	let input_value = input.value.toUpperCase();
	let ac = $('body.elo-overview main .searchbar .autocomplete-items');

	if (ac.length === 0) {
		ac = $("<div class=\'autocomplete-items\'></div>");
		searchbar.append(ac);
	} else {
		ac.empty();
	}
	acCurrentFocus = 0;

	let teams = $('.elo-list-team');
	teams.removeClass('ac-selected-team');

	if (input_value === "") {
		return;
	}
	let teams_list = [];
	for (const team of teams) {
		let team_name = $(team).find('.elo-list-item.team span.team-name')[0];
		teams_list.push([team_name.innerText,team.offsetTop,$(team)[0].getAttribute("data-teamid")]);
	}
	teams_list.sort(function(a,b) {return a[0] > b[0] ? 1 : -1});

	let first_hit = true;
	for (let i=0; i < teams_list.length; i++) {
		let indexOf = teams_list[i][0].toUpperCase().indexOf(input_value);
		if (indexOf > -1) {
			let ac_class = (first_hit) ? `class="autocomplete-active"` : "";
			first_hit = false;
			ac.append($(`<div ${ac_class}>${teams_list[i][0].substring(0,indexOf)}<strong>${teams_list[i][0].substring(indexOf,indexOf+input_value.length)}</strong>${teams_list[i][0].substring(indexOf+input_value.length)}
                    <input type='hidden' value='${teams_list[i][1]}'></div>`).click(function() {
				$('html').stop().animate({scrollTop: this.getElementsByTagName("input")[0].value-300}, 400, 'swing');
				$('body.elo-overview main .searchbar .autocomplete-items').empty();
				$('body.elo-overview main .searchbar input').val("");
				$("body.elo-overview main .searchbar button.search-clear").css("display","none");
				$('.elo-list-team').removeClass('ac-selected-team');
				$(`.elo-list-team[data-teamid=${teams_list[i][2]}]`).addClass('ac-selected-team');
			}));
		}
	}

	if (!($(input).hasClass("focus-listen"))) {
		input.addEventListener("keydown",function (e) {
			let autocomplete = $('body.elo-overview main .searchbar .autocomplete-items');
			let autocomplete_items = $('body.elo-overview main .searchbar .autocomplete-items div');
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
$(document).on('click', '.totop', to_top);

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
	$('.turnier-bonus-buttons .active').removeClass('active');
	$(this).addClass(['active','clickable']);
}
function team_nav_switch_active() {
	$('.team-titlebutton-wrapper .active').removeClass('active');
	$(this).addClass(['active','clickable']);
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
	let customsort_attribute = header_cell_current.hasClass("customsort");
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
		if (customsort_attribute) {
			sorting_array.unshift([rows.eq(i),rows.eq(i).find("td").eq(column).attr('data-customsort')]);
		} else {
			sorting_array.unshift([rows.eq(i),rows.eq(i).find("td").eq(column).text()]);
		}
	}
	function custom_parseFloat(int) {
		if (int === "-") {
			return -1;
		} else {
			return parseFloat(int);
		}
	}
	if (direction === "asc") {
		sorting_array.sort(function (a, b) {
			return custom_parseFloat(b[1]) - custom_parseFloat(a[1]);
		});
	} else {
		sorting_array.sort(function (a, b) {
			return custom_parseFloat(a[1]) - custom_parseFloat(b[1]);
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

function toggle_pt_table_columns() {
	event.preventDefault();
	let checkbox = $('input.pt-moreinfo-checkbox');
	let cookie_expiry = new Date();
	cookie_expiry.setFullYear(cookie_expiry.getFullYear()+1);
	if (checkbox.prop('checked')) {
		$('.playertable .kda_col').addClass("hidden");
		checkbox.prop('checked', false);
		document.cookie = `preference_ptextended=0; expires=${cookie_expiry}; path=/`;
	} else {
		$('.playertable .kda_col').removeClass("hidden");
		checkbox.prop('checked', true);
		document.cookie = `preference_ptextended=1; expires=${cookie_expiry}; path=/`;
	}
}
$(document).ready(function () {
	$('.playertable-header button.pt-moreinfo').on("click",toggle_pt_table_columns);
});

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
	$('div.playertable-header button.pt-collapse-all').on("click",collapse_all_tables);
	$('div.playertable-header button.pt-expand-all').on("click",expand_all_tables);
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
		fetch(`/admin/ajax/fragment/add-patches-rows?type=${selection}`, {
			method:"GET",
			signal: patch_view_fetch_control.signal
		})
			.then(res => {
				if (res.ok) {
					return res.json();
				} else {
					return {"html": "Fehler beim Laden der Daten"};
				}
			})
			.then(patches => {
				loading_indicator.css("display","none");
				element.html(patches.html);
			})
			.catch(err => {
				loading_indicator.css("display","none");
				element.html("Fehler beim Laden der Daten");
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
	let summoner = $(this).attr("data-name");
	let table = $(`.playerstable h4[data-name="${summoner}"]`).parent();
	if (!current_is){
		let marked_elsewhere = buttonJ.parent().parent().parent().find(`.role-playername.selected-player-table[data-name="${summoner}"]`);
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
	$('div.roleplayers .role-playername').on("click",select_player_table);
});

// toggle summonercard expansion
function expand_collapse_summonercard() {
	event.preventDefault();
	let sc = $(".summoner-card-wrapper .summoner-card");
	let collapse_button = $('.exp_coll_sc');
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
$(document).on("click", "button.exp_coll_sc", expand_collapse_summonercard);

// player page search
let player_search_controller = null;
function search_players() {
	if (player_search_controller !== null) player_search_controller.abort();
	player_search_controller = new AbortController();

	let searchbar = $('body.players main .searchbar');
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

	fragmentLoader(`player-search-cards-by-search?search=${input_value}`, player_search_controller.signal)
		.then(player_cards => {
			$('.search-loading-indicator').remove();
			recents_list.css("display","none");
			player_list.html(player_cards);
		})
}
async function reload_recent_players(initial=false) {
	let player_list = $('.recent-players-list');
	let recents = localStorage.getItem("searched_players_IDs");
	if (JSON.parse(recents) == null || JSON.parse(recents).length === 0) {
		player_list.html("");
		return;
	}

	fragmentLoader(`player-search-cards-by-recents?playerIds=${localStorage.getItem("searched_players_IDs")}`)
		.then(player_cards => {
			$('.search-loading-indicator').remove();
			if (initial) {
				player_list.hide();
			}
			player_list.html(`<span>${get_material_icon("history")}Zuletzt gesucht:</span>${player_cards}`);
			if (initial) {
				player_list.fadeIn(200);
			}
		})
}
$(document).on("click",".x-remove-recent-player", function() {
	remove_recent_player(this.dataset.playerid)
});
function remove_recent_player(playerid) {
	event.preventDefault();
	let recents = JSON.parse(localStorage.getItem("searched_players_IDs"));
	if (recents === null) {
		return;
	}
	let index = recents.indexOf(playerid);
	recents.splice(index,1);
	localStorage.setItem("searched_players_IDs",JSON.stringify(recents));
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


$(document).on("click", ".player-card-div.player-card-more", function () {
	expand_playercard(this);
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
$(document).on("click",".expand-pcards[data-action=expand]", () => expand_all_playercards());
$(document).on("click",".expand-pcards[data-action=collapse]", () => expand_all_playercards(true));
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

function show_teamcard_roster(event) {
	event.preventDefault();
	$(event.currentTarget).parent().toggleClass("roster-shown");
}
$(document).ready(function () {
	$('.team-card-playeramount').on("click", show_teamcard_roster);
});


let header_search_controller = null;
let header_search_acCurrentFocus = 0;
function header_search() {
	if (header_search_controller !== null) header_search_controller.abort();
	header_search_controller = new AbortController();

	let searchbar = $('header .searchbar');
	let input = $('header .searchbar input.search-all')[0];
	let input_value = input.value.toUpperCase();
	let loading_indicator = $('.search-loading-indicator');
	let ac = $('header .searchbar .autocomplete-items');

	if (ac.length === 0) {
		ac = $("<div class=\'autocomplete-items\'></div>");
		searchbar.append(ac);
	} else {
		ac.empty();
	}

	if (input_value.length < 2) {
		loading_indicator.remove();
		return;
	}
	if (loading_indicator.length > 0) {
		loading_indicator.remove();
	}
	searchbar.append("<div class='search-loading-indicator'></div>");

	fetch(`/api/search/global?search=${input_value}`, {
		method: "GET",
		signal: header_search_controller.signal,
	})
		.then(res => res.json())
		.then(search_results => {
			$('.search-loading-indicator').remove();

			for (let i = 0; i <search_results.length; i++) {
				let icon = "";
				let link = "";
				let additional = "";
				let ac_class = (i===0) ? `class="autocomplete-active"` : "";
				if (search_results[i]["type"] === "team") {
					icon = `<span class='material-symbol'>${get_material_icon("groups",true)}</span>`;
					link = `/team/${search_results[i]["id"]}`;
				} else if (search_results[i]["type"] === "player") {
					icon = `<span class='material-symbol'>${get_material_icon("person",true)}</span>`;
					link = `/spieler/${search_results[i]["id"]}`;
					additional = (search_results[i]["riotIdName"] !== null) ? `<br>(${search_results[i]["riotIdName"]}#${search_results[i]["riotIdTag"]})` : "";
				}
				ac.append($(`<a href="${link}" ${ac_class}>${icon}${search_results[i]["name"]}${additional}</a>`));
			}

			if (!($(input).hasClass("focus-listen"))) {
				input.addEventListener("keydown",function (e) {
					let autocomplete = $('header .searchbar .autocomplete-items');
					let autocomplete_items = $('header .searchbar .autocomplete-items a');
					if(autocomplete_items.length > 0) {
						if (e.keyCode === 40) {
							e.preventDefault();
							header_search_acCurrentFocus++;
							autocomplete_items.removeClass("autocomplete-active");
							if (header_search_acCurrentFocus >= autocomplete_items.length) header_search_acCurrentFocus = 0;
							if (header_search_acCurrentFocus < 0) header_search_acCurrentFocus = (autocomplete_items.length - 1);
							autocomplete_items[header_search_acCurrentFocus].classList.add("autocomplete-active");
							if (!(autocomplete[0].scrollTop+autocomplete[0].offsetHeight-autocomplete_items[header_search_acCurrentFocus].offsetHeight >= autocomplete_items[header_search_acCurrentFocus].offsetTop) || !(autocomplete[0].scrollTop <= autocomplete_items[header_search_acCurrentFocus].offsetTop)) {
								autocomplete.stop().animate({scrollTop: autocomplete_items[header_search_acCurrentFocus].offsetTop-autocomplete[0].offsetHeight+autocomplete_items[header_search_acCurrentFocus].offsetHeight}, 100, 'swing');
							}
						} else if (e.keyCode === 38) {
							e.preventDefault();
							header_search_acCurrentFocus--;
							autocomplete_items.removeClass("autocomplete-active");
							if (header_search_acCurrentFocus >= autocomplete_items.length) header_search_acCurrentFocus = 0;
							if (header_search_acCurrentFocus < 0) header_search_acCurrentFocus = (autocomplete_items.length - 1);
							autocomplete_items[header_search_acCurrentFocus].classList.add("autocomplete-active");
							if (!(autocomplete[0].scrollTop+autocomplete[0].offsetHeight-autocomplete_items[header_search_acCurrentFocus].offsetHeight >= autocomplete_items[header_search_acCurrentFocus].offsetTop) || !(autocomplete[0].scrollTop <= autocomplete_items[header_search_acCurrentFocus].offsetTop)) {
								autocomplete.stop().animate({scrollTop: autocomplete_items[header_search_acCurrentFocus].offsetTop}, 100, 'swing');
							}
						} else if (e.keyCode === 13) {
							if (header_search_acCurrentFocus < 0) header_search_acCurrentFocus = 0;
							e.preventDefault();
							autocomplete_items[header_search_acCurrentFocus].click();
						}
					}
				});
				$(input).addClass("focus-listen");
			}

		})
		.catch(error => {
			$('.search-loading-indicator').remove();
			if (error.name === "AbortError") {
				console.log(error)
			} else {
				console.error(error)
			}
		});
}

$(document).ready(function () {
	$('header .searchbar input').on("input",header_search);
});

function toggle_active_rankedsplit(tournament_id, season_split) {
	let radio_buttons = $('.ranked-settings-popover input');
	radio_buttons.prop("disabled", true);

	let splits = getCookie("tournament_ranked_splits");
	splits = (splits === "") ? {} : JSON.parse(splits);
	splits[tournament_id] = season_split;
	let cookiejson = JSON.stringify(splits);

	let cookie_expiry = new Date();
	cookie_expiry.setFullYear(cookie_expiry.getFullYear()+1);
	document.cookie = `tournament_ranked_splits=${cookiejson}; expires=${cookie_expiry}; path=/`;

	let url = new URL(window.location.href);
	if (url.pathname.endsWith("/elo")) {
		let filter_button = $(`.filter-button-wrapper button.filterb.active`).eq(0);
		if (filter_button.hasClass("all-teams")) {
			switch_elo_view(tournament_id, "all-teams");
		} else if (filter_button.hasClass("div-teams")) {
			switch_elo_view(tournament_id, "div-teams");
		} else if (filter_button.hasClass("group-teams")) {
			switch_elo_view(tournament_id, "group-teams");
		}
	}

	let season_split_show = season_split.split("-");
	if (season_split_show[1] === "0") {
		season_split_show = season_split_show[0];
	} else {
		season_split_show = season_split;
	}

	let ranked_elements = $(".split_rank_element");
	let current_ranked_elements = $(`.ranked-split-${season_split}`);

	ranked_elements.css("display", "none");
	current_ranked_elements.css("display","");

	let ranked_split_display = $("button.ranked-settings span");
	ranked_split_display.text(season_split_show);

	radio_buttons.prop("disabled", false);
}
$(document).ready(function () {
	$('.ranked-settings-popover input').on("change",function () {toggle_active_rankedsplit(this.getAttribute("data-tournament"), this.value)});
});

// allgemeine Helper

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

let team_event_switch_control = null;
function switch_team_event(page, event_id, team_id, playoff_id = null) {
	const buttons = $(`#teampage_switch_group_buttons .teampage_switch_group`);
	const button = $(`#teampage_switch_group_buttons .teampage_switch_group[data-group=${event_id}]`);

	buttons.removeClass("active");
	button.addClass("active");

	if (team_event_switch_control !== null) team_event_switch_control.abort();
	team_event_switch_control = new AbortController();

	if (page === "details") {
		if ($('.content-loading-indicator').length === 0) $('body').append("<div class='content-loading-indicator'></div>");
		fragmentLoader(`event-stage-view?tournamentId=${event_id}&teamId=${team_id}`,team_event_switch_control.signal)
			.then(content => {
				$(".inner-content").empty().append(content);
				$(".content-loading-indicator").remove();
				drawAllBracketLines();
			})
			.catch(error => {
				$(".content-loading-indicator").remove();
			})
	} else if (page === "matchhistory") {
		if ($('.content-loading-indicator').length === 0) $('body').append("<div class='content-loading-indicator'></div>");
		fragmentLoader(`match-history?teamId=${team_id}&tournamentStageId=${event_id}`, team_event_switch_control.signal)
			.then(matchhistory => {
				$("div.round-wrapper").remove();
				$("div.divider.rounds").remove();
				$("#teampage_switch_group_buttons").after(matchhistory);
				$('.content-loading-indicator').remove();
			})
			.catch(error => {
				$('.content-loading-indicator').remove();
			})
	}
}
$(()=>{
	$(".teampage_switch_group").on("click", function () {
		const groupID = $(this).attr("data-group");
		const teamID = $(this).attr("data-team");
		const playoffID = $(this).attr("data-playoff") ?? null;
		const tournamentID = $(this).attr("data-tournament") ?? null;
		const pagetype = $("body").hasClass("match-history") ? "matchhistory" : "details";
		switch_team_event(pagetype,groupID,teamID,playoffID);
	});
})

function switch_tournament_stage(stage) {
	$(`div.divisions-list`).css("display", "none");
	$(`div.divisions-list.${stage}`).css("display", "flex");
	$(`button.tournamentpage_switch_stage`).removeClass("active");
	$(`button.tournamentpage_switch_stage[data-stage=${stage}]`).addClass("active");
}
function switch_elolist_stage(tournamentID,stage) {
    $(`button.elolist_switch_stage`).removeClass("active");
    $(`button.elolist_switch_stage[data-stage=${stage}]`).addClass("active");
	switch_elo_view(tournamentID,"all-teams",stage);
}
$(()=>{
	$(".tournamentpage_switch_stage").on("click", function () {
		const stage = $(this).attr("data-stage");
		switch_tournament_stage(stage);
	});
	$(".elolist_switch_stage").on("click", function () {
		const stage = $(this).attr("data-stage");
		const tournamentID = $(this).attr("data-tournament");
		switch_elolist_stage(tournamentID,stage);
	});
})