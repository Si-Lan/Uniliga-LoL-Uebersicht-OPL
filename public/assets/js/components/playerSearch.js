import fragmentLoader from "../fragmentLoader";
import get_material_icon from "../utils/materialIcons";

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
export async function reload_recent_players(initial=false) {
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