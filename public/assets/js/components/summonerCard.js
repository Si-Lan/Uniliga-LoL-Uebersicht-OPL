import get_material_icon from "../utils/materialIcons";

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
    opgg_button.attr('href', opgg_link.toString());
    opgg_button_num.text(`(${player_amount} Spieler)`);
}

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