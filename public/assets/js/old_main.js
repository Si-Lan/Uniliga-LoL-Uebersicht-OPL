// allgemeine Such-Funktionen müssen erst noch hier bleiben, da Suchleisten noch nicht ordentlich modularisiert sind
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