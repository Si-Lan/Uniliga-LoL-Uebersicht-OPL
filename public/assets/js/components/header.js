import get_material_icon from "../utils/materialIcons";

$(document).on('click', '.settings-option.login', function (event) {
    event.preventDefault();
    document.getElementById("login-dialog").showModal();
    toggle_settings_menu(false);
    document.getElementById("keypass").focus();
})
$(document).on('click', 'header .settings-button', function (event) {
    toggle_settings_menu(null, event)
});
$(document).on('click', 'header .notifications-button', function (event) {
    toggle_notifications_menu(null, event)
});

function toggle_settings_menu(to_state = null, event = null) {
    if (event !== null) event.preventDefault();
    let notifications = $('.notifications-menu');
    let settings = $('.settings-menu');
    let settings_icon = $('.settings-button.material-symbol');

    if (to_state == null) {
        to_state = !settings.hasClass("shown");
    }
    if (to_state) {
        notifications.removeClass("shown");
        settings.addClass("shown");
        settings_icon.addClass("flipy");
    } else {
        settings.removeClass("shown")
        settings_icon.removeClass("flipy");
    }
}
function toggle_notifications_menu(to_state = null, event = null) {
    if (event !== null) event.preventDefault();
    let notifications = $('.notifications-menu');
    let settings = $('.settings-menu');
    let settings_icon = $('.settings-button.material-symbol');

    if (to_state == null) {
        to_state = !notifications.hasClass("shown");
    }
    if (to_state) {
        notifications.addClass("shown");
        settings.removeClass("shown");
        settings_icon.removeClass("flipy");
    } else {
        notifications.removeClass("shown");
    }
}
async function toggle_darkmode() {
    event.preventDefault();
    document.getElementsByTagName("body")[0].style.transition = "none";
    let tags = document.getElementsByTagName("body")[0].getElementsByTagName("*");
    let tag_num = tags.length;
    for (let i = 0; i < tag_num; i++) {
        tags[i].style.transition = "none";
    }
    let cookie_expiry = new Date();
    cookie_expiry.setFullYear(cookie_expiry.getFullYear()+1);
    let body = $('body');
    if (body.hasClass("light")) {
        // switch to darkmode
        body.removeClass("light");
        await new Promise(r => setTimeout(r, 1));
        $('.settings-option.toggle-mode').html(get_material_icon("dark_mode"));
        $('img.color-switch').each(function() {
            let img = $(this);
            let logo_url = img.attr("src");
            $(this).attr('src',logo_url.replace("logo_light.webp","logo.webp"));
        });
        document.cookie = `lightmode=0; expires=${cookie_expiry}; path=/`;
    } else {
        // switch to lightmode
        body.addClass("light");
        await new Promise(r => setTimeout(r, 1));
        $('.settings-option.toggle-mode').html(get_material_icon("light_mode"));
        $('img.color-switch').each(function() {
            let img = $(this);
            let logo_url = img.attr("src");
            $(this).attr('src',logo_url.replace("logo.webp","logo_light.webp"));
        });
        document.cookie = `lightmode=1; expires=${cookie_expiry}; path=/`;
    }
    await new Promise(r => setTimeout(r, 10));
    document.getElementsByTagName("body")[0].style.transition = null;
    for (let i = 0; i < tag_num; i++) {
        tags[i].style.transition = null;
    }
}

$(document).ready(function () {
    $('header .settings-option.toggle-mode').on("click",toggle_darkmode);
    let settings = $('.settings-menu');
    let notifications = $('.notifications-menu');
    let header = $('header');
    window.addEventListener("click", (event) => {
        if (settings.hasClass('shown') || notifications.hasClass('shown')) {
            if (!$.contains(header.get(0),$(event.target).get(0)) && event.target !== header[0]) {
                toggle_settings_menu(false);
                toggle_notifications_menu(false);
            }
        }
    });
    window.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && ($('header .settings-menu').hasClass('shown') || $('header .notifications-menu').hasClass('shown'))) {
            toggle_settings_menu(false);
            toggle_notifications_menu(false);
        }
    });
});

$(document).ready(function() {
    let encMail = "aW5mb0BzaWxlbmNlLmxvbA==";
    $(".settings-option.feedback").attr("href",`mailto:${atob(encMail)}`);
});


let header_search_controller = null;
let header_search_acCurrentFocus = 0;
function header_search() {
    if (header_search_controller !== null) header_search_controller.abort();
    header_search_controller = new AbortController();

    let searchbar = $('header .searchbar');
    let input = $('header .searchbar input.search-all')[0];
    let input_value = input.value.toUpperCase();
    let loading_indicator = $('.search-loading-indicator');
    let ac = $('header .searchbar .autocomplete-items');

    if (ac.length === 0) {
        ac = $("<div class=\'autocomplete-items\'></div>");
        searchbar.append(ac);
    } else {
        ac.empty();
    }

    if (input_value.length < 2) {
        loading_indicator.remove();
        return;
    }
    if (loading_indicator.length > 0) {
        loading_indicator.remove();
    }
    searchbar.append("<div class='search-loading-indicator'></div>");

    fetch(`/api/search/global?search=${input_value}`, {
        method: "GET",
        signal: header_search_controller.signal,
    })
        .then(res => res.json())
        .then(search_results => {
            $('.search-loading-indicator').remove();

            for (let i = 0; i <search_results.length; i++) {
                let icon = "";
                let link = "";
                let additional = "";
                let ac_class = (i===0) ? `class="autocomplete-active"` : "";
                if (search_results[i]["type"] === "team") {
                    icon = `<span class='material-symbol'>${get_material_icon("groups",true)}</span>`;
                    link = `/team/${search_results[i]["id"]}`;
                } else if (search_results[i]["type"] === "player") {
                    icon = `<span class='material-symbol'>${get_material_icon("person",true)}</span>`;
                    link = `/spieler/${search_results[i]["id"]}`;
                    additional = (search_results[i]["riotIdName"] !== null) ? `<br>(${search_results[i]["riotIdName"]}#${search_results[i]["riotIdTag"]})` : "";
                }
                ac.append($(`<a href="${link}" ${ac_class}>${icon}${search_results[i]["name"]}${additional}</a>`));
            }

            if (!($(input).hasClass("focus-listen"))) {
                input.addEventListener("keydown",function (e) {
                    let autocomplete = $('header .searchbar .autocomplete-items');
                    let autocomplete_items = $('header .searchbar .autocomplete-items a');
                    if(autocomplete_items.length > 0) {
                        if (e.keyCode === 40) {
                            e.preventDefault();
                            header_search_acCurrentFocus++;
                            autocomplete_items.removeClass("autocomplete-active");
                            if (header_search_acCurrentFocus >= autocomplete_items.length) header_search_acCurrentFocus = 0;
                            if (header_search_acCurrentFocus < 0) header_search_acCurrentFocus = (autocomplete_items.length - 1);
                            autocomplete_items[header_search_acCurrentFocus].classList.add("autocomplete-active");
                            if (!(autocomplete[0].scrollTop+autocomplete[0].offsetHeight-autocomplete_items[header_search_acCurrentFocus].offsetHeight >= autocomplete_items[header_search_acCurrentFocus].offsetTop) || !(autocomplete[0].scrollTop <= autocomplete_items[header_search_acCurrentFocus].offsetTop)) {
                                autocomplete.stop().animate({scrollTop: autocomplete_items[header_search_acCurrentFocus].offsetTop-autocomplete[0].offsetHeight+autocomplete_items[header_search_acCurrentFocus].offsetHeight}, 100, 'swing');
                            }
                        } else if (e.keyCode === 38) {
                            e.preventDefault();
                            header_search_acCurrentFocus--;
                            autocomplete_items.removeClass("autocomplete-active");
                            if (header_search_acCurrentFocus >= autocomplete_items.length) header_search_acCurrentFocus = 0;
                            if (header_search_acCurrentFocus < 0) header_search_acCurrentFocus = (autocomplete_items.length - 1);
                            autocomplete_items[header_search_acCurrentFocus].classList.add("autocomplete-active");
                            if (!(autocomplete[0].scrollTop+autocomplete[0].offsetHeight-autocomplete_items[header_search_acCurrentFocus].offsetHeight >= autocomplete_items[header_search_acCurrentFocus].offsetTop) || !(autocomplete[0].scrollTop <= autocomplete_items[header_search_acCurrentFocus].offsetTop)) {
                                autocomplete.stop().animate({scrollTop: autocomplete_items[header_search_acCurrentFocus].offsetTop}, 100, 'swing');
                            }
                        } else if (e.keyCode === 13) {
                            if (header_search_acCurrentFocus < 0) header_search_acCurrentFocus = 0;
                            e.preventDefault();
                            autocomplete_items[header_search_acCurrentFocus].click();
                        }
                    }
                });
                $(input).addClass("focus-listen");
            }

        })
        .catch(error => {
            $('.search-loading-indicator').remove();
            if (error.name === "AbortError") {
                console.log(error)
            } else {
                console.error(error)
            }
        });
}

$(document).ready(function () {
    $('header .searchbar input').on("input",header_search);
});