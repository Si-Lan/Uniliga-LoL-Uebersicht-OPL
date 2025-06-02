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
	let popup_loader = popup.find('.popup-loading-indicator');
	popup_loader.css("opacity","0");
	await new Promise(r => setTimeout(r, 210));
	popup_loader.remove();
}
function add_popupLoadingIndicator(popup) {
	popup.prepend("<div class='popup-loading-indicator'></div>");
}