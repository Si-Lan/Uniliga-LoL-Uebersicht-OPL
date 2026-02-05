export function setButtonUpdating(button) {
    setButtonLoadingBarWidth(button, 0);
    button.addClass("button-updating");
    button.prop("disabled",true);
}

export function unsetButtonUpdating(button) {
    button.removeClass("button-updating");
    button.prop("disabled",false);
    setButtonLoadingBarWidth(button, 0);
}

export function finishButtonUpdating(button) {
    setButtonLoadingBarWidth(button, 100);
    setTimeout(() => unsetButtonUpdating(button), 100);
}

export function setButtonLoadingBarWidth(button, widthPercentage) {
    button.attr("style", `--loading-bar-width: ${widthPercentage}%`);
}


export function setUserButtonUpdating(button) {
    setUserButtonLoadingBarWidth(button, 0);
    button.addClass("user_updating");
    button.prop("disabled",true);
}
export function unsetUserButtonUpdating(button) {
    button.removeClass("user_updating");
    button.prop("disabled", false);
    setUserButtonLoadingBarWidth(button, 0);
}
export function finishUserButtonUpdating(button) {
    setUserButtonLoadingBarWidth(button, 100);
    setTimeout(() => unsetUserButtonUpdating(button), 100);
}
export function setUserButtonLoadingBarWidth(button, widthPercentage) {
    button.attr("style", `--update-loading-bar-width: ${widthPercentage}%`);
}