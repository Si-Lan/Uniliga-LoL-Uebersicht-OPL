function switch_tournament_stage(stage) {
    $(`div.divisions-list`).css("display", "none");
    $(`div.divisions-list.${stage}`).css("display", "flex");
    $(`button.tournamentpage_switch_stage`).removeClass("active");
    $(`button.tournamentpage_switch_stage[data-stage=${stage}]`).addClass("active");
}
$(document).on("click", ".tournamentpage_switch_stage", function () {
    const stage = $(this).attr("data-stage");
    switch_tournament_stage(stage);
});