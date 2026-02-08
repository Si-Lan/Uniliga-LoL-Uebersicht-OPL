import fragmentLoader from "../fragmentLoader";

$(document).on('click', 'button.add-suggestion-get-games', function(){
    const matchupId = $(this).data('matchup-id');
    if (matchupId === "") return;

    const select = $(this).siblings('label.slct').find('select');
    const playerId = select.val();
    if (playerId === "") return;

    const container = $(this).closest('.add-suggestion-form').find('.add-suggestion-games-list');

    fragmentLoader(`game-suggestions?matchupId=${matchupId}&playerId=${playerId}`)
        .then(content => {
            container.empty().append(content);
        })
        .catch(error => console.error(error))
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
                return;
            }
            if (response.created === false) {
                alert(response.message);
                return;
            }

            await fragmentLoader(`add-suggestion-popup-content?matchupId=${matchupId}`)
                .then(content => {
                    form.closest('dialog').find('.dialog-content').empty().append(content);
                });
            await new Promise(r => setTimeout(r, 100));
            alert(response.message);
        })
        .catch(e => console.error(e))
})