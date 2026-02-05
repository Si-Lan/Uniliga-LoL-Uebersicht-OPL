import fragmentLoader from "../fragmentLoader";
$(document).on('click', '.filter-button-wrapper button.filterb', function () {
    switch_elo_view(this.dataset.id, this.dataset.view);
});
$(document).on('click', '.settings-button-wrapper button.color-elo-list', () => color_elo_list());
export function switch_elo_view(tournamentID,view) {
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



function switch_elolist_stage(tournamentID,stage) {
    $(`button.elolist_switch_stage`).removeClass("active");
    $(`button.elolist_switch_stage[data-stage=${stage}]`).addClass("active");
    switch_elo_view(tournamentID,"all-teams",stage);
}
$(document).on('click', '.elolist_switch_stage', function () {
    const stage = $(this).attr("data-stage");
    const tournamentID = $(this).attr("data-tournament");
    switch_elolist_stage(tournamentID,stage);
})