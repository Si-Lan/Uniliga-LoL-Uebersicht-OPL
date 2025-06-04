$(document).on('click', 'dialog.dismissable-popup', function (event) {
	let rect = this.getBoundingClientRect();
	let isInDialog = (rect.top <= event.clientY && event.clientY <= rect.bottom) && (rect.left <= event.clientX && event.clientX <= rect.right);
	if (event.target === this && !isInDialog) this.close();
});
$(document).on("click", "dialog .close-popup", function() {this.closest("dialog").close()});
$(document).on("close", "dialog.clear-on-exit", function() {$(this).find(".dialog-content").empty()});
$(()=>{
	$("dialog.modalopen_auto").get().forEach(element => element.showModal());
})

$(document).on("click", "[data-dialog-id]", function() {
	let dialog = $(`dialog#${this.dataset.dialogId}:not(.page-popup)`);
	if (dialog.length === 0) {
		return;
	}
	dialog[0].showModal();
});

async function remove_popupLoadingIndicator(popup) {
	let popup_loader = $(popup).find('.popup-loading-indicator');
	popup_loader.css("opacity","0");
	await new Promise(r => setTimeout(r, 210));
	popup_loader.remove();
}
function add_popupLoadingIndicator(popup) {
	$(popup).prepend("<div class='popup-loading-indicator'></div>");
}

function setDialogProgressBar(dialog, percentValue) {
	if (!dialog.hasClass("has-dialog-loading-bar")) dialog.addClass("has-dialog-loading-bar");
	dialog.attr("style",`--loading-bar-width:${percentValue}%`);
}
function addToDialogProgressBar(dialog, percentValue) {
	if (!dialog.hasClass("has-dialog-loading-bar")) dialog.addClass("has-dialog-loading-bar");

	let progressStr = getComputedStyle(dialog[0]).getPropertyValue("--loading-bar-width").trim();
	let progressVal = parseFloat(progressStr);
	dialog.attr("style",`--loading-bar-width:${progressVal + percentValue}%`);
}
async function resetDialogProgressBar(dialog) {
	await new Promise(r => setTimeout(r, 200));
	dialog.addClass("hidden-loading-bar");
	await new Promise(r => setTimeout(r, 200));
	dialog.attr("style",`--loading-bar-width:0`);
	await new Promise(r => setTimeout(r, 200));
	dialog.removeClass("hidden-loading-bar");
}