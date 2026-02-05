import {abortController} from "../utils/abortControllerManager";

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

export async function remove_popupLoadingIndicator(popup) {
	let popup_loader = $(popup).find('.popup-loading-indicator');
	popup_loader.css("opacity","0");
	await new Promise(r => setTimeout(r, 210));
	popup_loader.remove();
}
export function add_popupLoadingIndicator(popup) {
	$(popup).prepend("<div class='popup-loading-indicator'></div>");
}

export function setDialogProgressBar(dialog, percentValue) {
	if (!dialog.hasClass("has-dialog-loading-bar")) dialog.addClass("has-dialog-loading-bar");
	dialog.attr("style",`--loading-bar-width:${percentValue}%`);
}
export function addToDialogProgressBar(dialog, percentValue) {
	if (!dialog.hasClass("has-dialog-loading-bar")) dialog.addClass("has-dialog-loading-bar");

	let progressStr = getComputedStyle(dialog[0]).getPropertyValue("--loading-bar-width").trim();
	let progressVal = parseFloat(progressStr);
	dialog.attr("style",`--loading-bar-width:${progressVal + percentValue}%`);
}
export async function resetDialogProgressBar(dialog) {
	await new Promise(r => setTimeout(r, 200));
	dialog.addClass("hidden-loading-bar");
	await new Promise(r => setTimeout(r, 200));
	dialog.attr("style",`--loading-bar-width:0`);
	await new Promise(r => setTimeout(r, 200));
	dialog.removeClass("hidden-loading-bar");
}


export function bindDialogCloseHandler(dialog) {
	if (dialog.hasAttribute('data-close-bound')) {
		return;
	}

	let bound = false;

	if (dialog.classList.contains('clear-on-exit')) {
		bound = true;
		dialog.addEventListener('close', async () => {
			await new Promise(r => setTimeout(r, 200)); // Schließen Animation abwarten
			$(dialog).find(".dialog-content").empty();
		});
	}

	if (dialog.classList.contains('match-popup')) {
		bound = true;
		dialog.addEventListener('close', () => {
			let url = new URL(window.location.href);
			url.searchParams.delete('match');
			window.history.replaceState({}, '', url);
		});
	}

	if (dialog.classList.contains('related-events-dialog')) {
		bound = true;
		dialog.addEventListener('close', () => {
			resetDialogProgressBar($(dialog));
			abortController('relatedEventsPopup');
		});
	}


	if (bound) dialog.setAttribute('data-close-bound', 'true');
}

const dialogAddedObserver = new MutationObserver(mutations => {
	for (const mutation of mutations) {
		$(mutation.addedNodes).each(function () {
			$(this).find('dialog:not([data-close-bound])').each(function () {
				bindDialogCloseHandler(this);
			})
		})
	}
})

$(() => {
	$('dialog').each(function () {bindDialogCloseHandler(this)});
	dialogAddedObserver.observe(document.body, {childList: true, subtree: true});
})