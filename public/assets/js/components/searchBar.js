function clear_searchbar(button, event) {
    event.preventDefault();
    let searchbar = $(button.parentNode);
    searchbar.find("input[type=search]").val('').trigger('keyup').trigger('input').focus();
}
function toggle_clear_search_x(button) {
    let clear_button = $(button.parentNode).find(".search-clear");
    let input = $(button)[0].value;
    if (input === "") {
        clear_button.css("display", "none");
    } else {
        clear_button.css("display", "flex");
    }
}
$(document).on("click", ".searchbar .search-clear", function (e) {clear_searchbar(this, e)});
$(document).on("input", ".searchbar input[type=search]", function() {toggle_clear_search_x(this)});
$(document).on("mouseenter", ".searchbar input[type=search]", function() {toggle_clear_search_x(this)});