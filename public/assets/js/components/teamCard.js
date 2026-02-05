$(document).on("click", ".team-card-playeramount", function (e) {
    e.preventDefault();
    $(this).parent().toggleClass("roster-shown");
});