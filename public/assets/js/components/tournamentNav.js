import {getCookie} from "../utils/cookieHandler";
import {switch_elo_view} from "./eloList";

$(document).on('click', '.turnier-bonus-buttons .button', function() {
    $('.turnier-bonus-buttons .active').removeClass('active');
    $(this).addClass(['active','clickable']);
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
$(document).on("change", ".ranked-settings-popover input", function () {
    toggle_active_rankedsplit(this.getAttribute("data-tournament"), this.value)
});