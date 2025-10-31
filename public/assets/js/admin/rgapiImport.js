$(document).on("change", "select.tournament-selector", function () {
	$("div.writing-wrapper").addClass("hidden");
	$("div.writing-wrapper."+this.value).removeClass("hidden");
});