$(document).on("click", ".player-card-div.player-card-more", function () {
    expand_playercard(this);
});
function expand_playercard(card_button) {
    event.preventDefault();
    let card = card_button.parentNode;
    if (card.classList.contains("expanded-pcard")) {
        card.classList.remove("expanded-pcard");
    } else {
        card.classList.add("expanded-pcard");
    }
}
$(document).on("click",".expand-pcards[data-action=expand]", () => expand_all_playercards());
$(document).on("click",".expand-pcards[data-action=collapse]", () => expand_all_playercards(true));
function expand_all_playercards(collapse=false) {
    event.preventDefault();
    let cards = document.getElementsByClassName("player-card");
    for (const card of cards) {
        if (collapse) {
            card.classList.remove("expanded-pcard");
        } else {
            card.classList.add("expanded-pcard");
        }
    }
}