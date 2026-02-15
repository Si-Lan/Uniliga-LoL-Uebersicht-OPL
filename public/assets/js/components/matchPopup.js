import fragmentLoader from "../fragmentLoader";

$(document).on('click', 'button.add-suggestion-get-games', function(){
    const form = $(this).closest('.add-suggestion-form');
    const matchupId = $(this).data('matchup-id');
    if (matchupId === "") return;

    const select = $(this).siblings('label.slct').find('select');
    const playerId = select.val();

    const container = form.find('.add-suggestion-games-list');

    if (playerId === "") {
        container.empty().append("Wähle einen Spieler aus!");
        return;
    }

    let loadingIndicator = form.find('.content-loading-indicator');
    if (loadingIndicator.length === 0) form.append('<div class="content-loading-indicator"></div>');

    fragmentLoader(`game-suggestions?matchupId=${matchupId}&playerId=${playerId}`)
        .then(content => {
            container.empty().append(content);
            form.find('.content-loading-indicator').remove();
        })
        .catch(error => {
            console.error(error);
            form.find('.content-loading-indicator').remove();
        })
});

$(document).on('click', 'button.open-add-suggestion-form', function(){
    $(this).siblings('.add-suggestion-form').toggleClass('open');
})


$(document).on('click', 'button.send-suggestion', function(){
    const form = $(this).closest('.add-suggestion-form');
    const matchupId = this.dataset.matchupId;
    const team1Score = form.find('input[name="team1Score"]').val();
    const team2Score = form.find('input[name="team2Score"]').val();
    const games = [];
    form.find('.add-suggestion-games-list .game-suggestion-details').each(function(index, element){
        const checkbox = $(this).find('input[type="checkbox"]');
        if (checkbox.is(':checked')) games.push(element.dataset.gameId);
    });

    const array = {
        "matchupId": matchupId,
        "team1Score": team1Score,
        "team2Score": team2Score,
        "gameIds": games
    }

    if (team1Score === "" && team2Score === "" && games.length === 0) {
        alert("Keine Änderungen angegeben!");
        return;
    }

    let loadingIndicator = form.find('.content-loading-indicator');
    if (loadingIndicator.length === 0) form.append('<div class="content-loading-indicator"></div>');

    fetch('/api/suggestions', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(array)
    })
        .then(res => {
            if (res.ok) {
                return res.json();
            } else {
                return {"error": "Fehler beim Laden der Daten"};
            }
        })
        .then(async response => {
            if (response.error) {
                alert(response.error);
                form.find('.content-loading-indicator').remove();
                return;
            }
            if (response.created === false) {
                alert(response.message);
                form.find('.content-loading-indicator').remove();
                return;
            }

            await fragmentLoader(`add-suggestion-popup-content?matchupId=${matchupId}`)
                .then(content => {
                    form.closest('dialog').find('.dialog-content').empty().append(content);
                });
            await new Promise(r => setTimeout(r, 100));
            alert(response.message);
            form.find('.content-loading-indicator').remove();
        })
        .catch(e => {
            console.error(e);
            form.find('.content-loading-indicator').remove();
        })
})

$(document).on('click', 'button.accept-suggestion', function(){
    const suggestionId = this.dataset.suggestionId;
    const matchupId = this.dataset.matchupId;
    const existingSuggestions = $(this).closest('.existing-suggestions');
    const matchPopup = existingSuggestions.closest('dialog.match-popup');
    const matchPopupContent = matchPopup.find('.dialog-content');
    const openSuggestionsButton = matchPopup.find('button.suggest-match-changes');
    let teamParam = "";
    if (openSuggestionsButton.data('team-id')) {
        teamParam = `&teamId=${openSuggestionsButton.data('team-id')}`;
    }

    let loadingIndicator = existingSuggestions.find('.content-loading-indicator');
    if (loadingIndicator.length === 0) existingSuggestions.append('<div class="content-loading-indicator"></div>');

    fetch(`/admin/api/suggestions/${suggestionId}/accept`, {method: 'POST'})
        .then(res => {
            if (res.ok) {
                return res.json()
            } else {
                return {"error": "Fehler beim Laden der Daten"};
            }
        })
        .then(async response => {
            if (response.error) {
                alert(response.error);
                existingSuggestions.find('.content-loading-indicator').remove();
                return;
            }

            await fragmentLoader(`match-popup?matchId=${matchupId}${teamParam}`)
                .then(content => {
                    matchPopupContent.empty().append(content);
                });
            existingSuggestions.find('.content-loading-indicator').remove();
        })
        .catch(e =>  {
            console.error(e);
            existingSuggestions.find('.content-loading-indicator').remove();
        });
})

$(document).on('click', 'button.reject-suggestion', function(){
    const suggestionId = this.dataset.suggestionId;
    const matchupId = this.dataset.matchupId;
    const existingSuggestions = $(this).closest('.existing-suggestions');

    let loadingIndicator = existingSuggestions.find('.content-loading-indicator');
    if (loadingIndicator.length === 0) existingSuggestions.append('<div class="content-loading-indicator"></div>');

    fetch(`/admin/api/suggestions/${suggestionId}/reject`, {method: 'POST'})
        .then(res => {
            if (res.ok) {
                return res.json()
            } else {
                return {"error": "Fehler beim Laden der Daten"};
            }
        })
        .then(async response => {
            if (response.error) {
                alert(response.error);
                existingSuggestions.find('.content-loading-indicator').remove();
                return;
            }

            await fragmentLoader(`add-suggestion-popup-content?matchupId=${matchupId}`)
                .then(content => {
                    existingSuggestions.closest('.dialog-content').empty().append(content);
                });
            existingSuggestions.find('.content-loading-indicator').remove();
        })
        .catch(e => {
            console.error(e);
            existingSuggestions.find('.content-loading-indicator').remove();
        });
})

$(document).on('click', 'button.revert-suggestions', async function () {
    const matchupId = this.dataset.matchupId;
    const suggestionsPopup = $(this).closest('dialog');
    const matchPopup = suggestionsPopup.closest('dialog.match-popup');
    const matchPopupContent = matchPopup.find('.dialog-content');

    let loadingIndicator = suggestionsPopup.find('.content-loading-indicator');
    if (loadingIndicator.length === 0) suggestionsPopup.append('<div class="content-loading-indicator"></div>');

    await fetch(`/admin/api/suggestions/${matchupId}/revert`, {method: 'POST'})
        .then(res => {
            if (res.ok) {
                return res.json()
            } else {
                return {"error": "Fehler beim Laden der Daten"};
            }
        })
        .then(async response => {
            if (response.error) {
                alert(response.error);
                suggestionsPopup.find('.content-loading-indicator').remove();
                return;
            }

            await fragmentLoader(`match-popup?matchId=${matchupId}`)
                .then(content => {
                    matchPopupContent.empty().append(content);
                })
                .catch(e => console.error(e));
            suggestionsPopup.find('.content-loading-indicator').remove();
        })
        .catch(e => console.error(e));
    suggestionsPopup.find('.content-loading-indicator').remove();
})