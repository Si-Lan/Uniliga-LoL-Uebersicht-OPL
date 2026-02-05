import fragmentLoader from "../fragmentLoader";
import {drawAllBracketLines} from "./brackets";

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
$(document).on("click", ".teampage_switch_group", function () {
    const groupID = $(this).attr("data-group");
    const teamID = $(this).attr("data-team");
    const playoffID = $(this).attr("data-playoff") ?? null;
    const tournamentID = $(this).attr("data-tournament") ?? null;
    const pagetype = $("body").hasClass("match-history") ? "matchhistory" : "details";
    switch_team_event(pagetype,groupID,teamID,playoffID);
});