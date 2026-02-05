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