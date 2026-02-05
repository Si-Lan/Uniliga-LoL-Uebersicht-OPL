import get_material_icon from "../utils/materialIcons";

// teamstats table functions
function sort_table(element) {
    let column;
    $(element).parent().find('th').each(function(index) {
        if (this === element) {
            column = index;
            return false;
        }
    });
    let direction = "desc";
    let reverse = "asc";
    let table = $(element).parent().parent().parent();
    let header_row = table.find("tr").first();
    let header_cells = header_row.find("th");
    let header_cell_prev = header_row.find('th.sortedby').first();
    let header_cell_current = header_row.find("th").eq(column);
    let customsort_attribute = header_cell_current.hasClass("customsort");
    let newcolumn = !header_cell_current.hasClass("sortedby");
    if (newcolumn) {
        header_cells.removeClass("sortedby");
        header_cell_current.addClass("sortedby");
    } else {
        if (header_cell_current.hasClass("desc")) {
            direction = "asc";
            reverse = "desc";
        }
    }
    let rows = table.find("tr:not(.expand-table):not(:first)");
    let sorting_array = [];
    for (let i=0; i<rows.length; i++) {
        if (customsort_attribute) {
            sorting_array.unshift([rows.eq(i),rows.eq(i).find("td").eq(column).attr('data-customsort')]);
        } else {
            sorting_array.unshift([rows.eq(i),rows.eq(i).find("td").eq(column).text()]);
        }
    }
    function custom_parseFloat(int) {
        if (int === "-") {
            return -1;
        } else {
            return parseFloat(int);
        }
    }
    if (direction === "asc") {
        sorting_array.sort(function (a, b) {
            return custom_parseFloat(b[1]) - custom_parseFloat(a[1]);
        });
    } else {
        sorting_array.sort(function (a, b) {
            return custom_parseFloat(a[1]) - custom_parseFloat(b[1]);
        });
    }
    rows.remove();
    let head = table.find("tr:first");
    for (let i=0; i<sorting_array.length; i++) {
        head.after(sorting_array[i][0]);
    }
    header_cells.removeClass("asc");
    header_cells.removeClass("desc");
    header_cell_current.addClass(direction);
    header_cell_current.removeClass(reverse);
    if (newcolumn) {
        swap_sort_icons(header_cell_current,header_cell_prev);
    }

    rows = table.find("tr:not(.expand-table):not(:first)");
    rows.on("mouseover",mark_champ_in_table);
    rows.on("mouseleave",stop_mark_champ_in_table);
    rows.on("click",hard_mark_champ_in_table);
}
$(document).ready(function () {
    $('.stattables table th.sortable').on("click", function() {
        sort_table(this);
    });
});
async function swap_sort_icons(header_cell_current,header_cell_prev) {
    await unset_prev_sorticon(header_cell_prev);
    await set_current_sorticon(header_cell_current);
}
async function unset_prev_sorticon(header_cell_prev) {
    header_cell_prev.find("div.sort-direction").css("transition","transform 200ms");
    header_cell_prev.find("div.sort-direction").css("transform","rotateX(90deg)");
    await new Promise(r => setTimeout(r, 120));
    header_cell_prev.find("div.sort-direction").html(get_material_icon("check_indeterminate_small",true));
    header_cell_prev.find("div.sort-direction").css("transform","");
    header_cell_prev.find("div.sort-direction").css("transition","");
}
async function set_current_sorticon(header_cell_current) {
    header_cell_current.find("div.sort-direction").css("transition","transform 0s");
    header_cell_current.find("div.sort-direction").css("transform","rotateX(90deg)");
    header_cell_current.find("div.sort-direction").html(get_material_icon("expand_more",true));
    await new Promise(r => setTimeout(r, 10));
    header_cell_current.find("div.sort-direction").css("transition","transform 200ms");
    header_cell_current.find("div.sort-direction").css("transform","");
    header_cell_current.find("div.sort-direction").css("transition","");
}

function toggle_pt_table_columns() {
    event.preventDefault();
    let checkbox = $('input.pt-moreinfo-checkbox');
    let cookie_expiry = new Date();
    cookie_expiry.setFullYear(cookie_expiry.getFullYear()+1);
    if (checkbox.prop('checked')) {
        $('.playertable .kda_col').addClass("hidden");
        checkbox.prop('checked', false);
        document.cookie = `preference_ptextended=0; expires=${cookie_expiry}; path=/`;
    } else {
        $('.playertable .kda_col').removeClass("hidden");
        checkbox.prop('checked', true);
        document.cookie = `preference_ptextended=1; expires=${cookie_expiry}; path=/`;
    }
}
$(document).ready(function () {
    $('.playertable-header button.pt-moreinfo').on("click",toggle_pt_table_columns);
});

function expand_collapse_table() {
    let expand_button = $(this);
    let table = expand_button.parent().parent();
    if (table.hasClass("collapsed")) {
        table.removeClass("collapsed");
        table.addClass("expanded");
        let rows = $(table.find('tr')[5]).nextUntil('tr.expand-table');
        rows.find('td').wrapInner("<div style='display: none;'/>").parent().find('td>div').slideDown("fast",function () {
            let $set = $(this);
            $set.replaceWith($set.contents());
        });
    } else if (table.hasClass("expanded")) {
        let rows = $(table.find('tr')[5]).nextUntil('tr.expand-table');
        rows.find('td').wrapInner("<div style='display: block;'/>").parent().find('td>div').slideUp("fast",function () {
            table.removeClass("expanded");
            table.addClass("collapsed");
            let $set = $(this);
            $set.replaceWith($set.contents());
        });
    }
}
$(document).ready(function () {
    $('.stattables table tr.expand-table').on("click",expand_collapse_table);
});

function collapse_all_tables() {
    let buttons = $('.playertable tr.expand-table');
    for (const expand_button of buttons) {
        let table = $(expand_button).parent().parent();
        if (table.hasClass("expanded")) {
            let rows = $(table.find('tr')[5]).nextUntil('tr.expand-table');
            rows.find('td').wrapInner("<div style='display: block;'/>").parent().find('td>div').slideUp("fast",function () {
                table.removeClass("expanded");
                table.addClass("collapsed");
                let $set = $(this);
                $set.replaceWith($set.contents());
            });
        }
    }
}
function expand_all_tables() {
    let buttons = $('.playertable tr.expand-table');
    for (const expand_button of buttons) {
        let table = $(expand_button).parent().parent();
        if (table.hasClass("collapsed")) {
            table.removeClass("collapsed");
            table.addClass("expanded");
            let rows = $(table.find('tr')[5]).nextUntil('tr.expand-table');
            rows.find('td').wrapInner("<div style='display: none;'/>").parent().find('td>div').slideDown("fast",function () {
                let $set = $(this);
                $set.replaceWith($set.contents());
            });
        }
    }
}
$(document).ready(function () {
    $('div.playertable-header button.pt-collapse-all').on("click",collapse_all_tables);
    $('div.playertable-header button.pt-expand-all').on("click",expand_all_tables);
});

function mark_champ_in_table() {
    let tr_selector = $('.stattables .table tr');
    let champ = $(this).find('img').attr('alt');
    let activate = tr_selector.has(`td img[alt=${champ}]`);
    tr_selector.removeClass('temp-markedrow');
    activate.addClass('temp-markedrow');
    tr_selector.each(function (i,e) {
        var styleAttr = $(e).attr('class');
        if (!styleAttr || styleAttr === '') {
            $(e).removeAttr('class');
        }
    });
}
function hard_mark_champ_in_table() {
    let tr_selector = $('.stattables .table tr');
    if ($(this).find('th').length === 0 && !$(this).hasClass("expand-table")) {
        let champ = $(this).find('img').attr('alt');
        let activate = tr_selector.has(`td img[alt=${champ}]`);
        if (activate.hasClass("markedrow")) {
            activate.removeClass("markedrow");
        } else {
            tr_selector.removeClass("markedrow");
            tr_selector.each(function (i,e) {
                var styleAttr = $(e).attr('class');
                if (!styleAttr || styleAttr === '') {
                    $(e).removeAttr('class');
                }
            });
            activate.addClass("markedrow");
        }
    }
}
function stop_mark_champ_in_table() {
    let tr_selector = $('.stattables .table tr');
    tr_selector.removeClass('temp-markedrow');
    tr_selector.each(function (i,e) {
        var styleAttr = $(e).attr('class');
        if (!styleAttr || styleAttr === '') {
            $(e).removeAttr('class');
        }
    });
}
$(document).ready(function () {
    let table_row = $('.stattables .table tr');
    table_row.on("mouseover",mark_champ_in_table);
    table_row.on("mouseleave",stop_mark_champ_in_table);
    table_row.on("click",hard_mark_champ_in_table);
});


// stats toggle playertables
function select_player_table() {
    event.preventDefault();
    let buttonJ = $(this);
    let current_is = buttonJ.hasClass('selected-player-table');
    let summoner = $(this).attr("data-name");
    let table = $(`.playerstable h4[data-name="${summoner}"]`).parent();
    if (!current_is){
        let marked_elsewhere = buttonJ.parent().parent().parent().find(`.role-playername.selected-player-table[data-name="${summoner}"]`);
        marked_elsewhere.removeClass('selected-player-table');
        buttonJ.addClass('selected-player-table');
        let ind = 0;
        $('.teamroles .role:not(.svg-wrapper)').each(function(index) {
            if ($.contains(this,buttonJ[0])) {
                ind = index;
                return false;
            }
        });
        for (let i = 0; i < 5; i++) {
            table.removeClass(("role"+i));
        }
        for (let i = ind; i >= 0; i--) {
            let next_table = $(`.playertable.role${i}`);
            if (next_table.length > 0) {
                next_table.last().after(table);
                break;
            }
            if (i === 0) {
                $('.playertable').first().before(table);
            }
        }
        table.addClass(("role"+ind));
        table.removeClass('hidden-table');
    } else {
        buttonJ.removeClass('selected-player-table');
        table.addClass('hidden-table')
    }
}
$(document).ready(function () {
    $('div.roleplayers .role-playername').on("click",select_player_table);
});