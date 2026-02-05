function open_dropdown_selection(dropdown) {
    event.preventDefault();
    $(dropdown).toggleClass('open-selection');
}
async function select_dropdown_option(selection) {
    event.preventDefault();
    const selectionJQ = $(selection);
    let button = selectionJQ.parent().parent().find(".button-dropdown");
    let icon_div = button.find('.material-symbol')[0].innerHTML;
    button.html(selection.innerText + `<span class='material-symbol'>${icon_div}</span>`);
    selectionJQ.parent().find(".dropdown-selection-item").removeClass('selected-item');
    selectionJQ.addClass('selected-item');
    handle_dropdown_selection(button[0].getAttribute("data-dropdowntype"), selection.getAttribute("data-selection"));
    await new Promise(r => setTimeout(r, 1));
    button.removeClass("open-selection");
}
let patch_view_fetch_control = null;
function handle_dropdown_selection(type, selection) {
    if (type === "stat-tables") {
        let entTable = $(".champstattables.entire");
        let singTable = $(".champstattables.singles");
        if (selection === "all") {
            entTable.css("display","flex");
            singTable.css("display","none");
        } else if (selection === "single") {
            singTable.css("display","flex");
            entTable.css("display","none");
        }
    }
    if (type === "get-patches") {
        let element = $('dialog.add-patch-popup .add-patches-display');
        let loading_indicator = $('dialog.add-patch-popup .popup-loading-indicator');
        loading_indicator.css("display","");
        if (patch_view_fetch_control !== null) patch_view_fetch_control.abort();
        patch_view_fetch_control = new AbortController();
        fetch(`/admin/ajax/fragment/add-patches-rows?type=${selection}`, {
            method:"GET",
            signal: patch_view_fetch_control.signal
        })
            .then(res => {
                if (res.ok) {
                    return res.json();
                } else {
                    return {"html": "Fehler beim Laden der Daten"};
                }
            })
            .then(patches => {
                loading_indicator.css("display","none");
                element.html(patches.html);
            })
            .catch(err => {
                loading_indicator.css("display","none");
                element.html("Fehler beim Laden der Daten");
            })
    }
}
$(document).on('click', 'div.dropdown-selection .dropdown-selection-item', function () {
    select_dropdown_option(this);
});
$(document).on('click', '.button-dropdown', function () {
    open_dropdown_selection(this);
});