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
            console.log(container[0]);
            container.empty().append(content);
        })
        .catch(error => console.error(error))
});

$(document).on('click', 'button.open-add-suggestion-form', function(){
    $(this).siblings('.add-suggestion-form').toggleClass('open');
})