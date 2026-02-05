$(document).on('click', '.team-titlebutton-wrapper .button', function() {
    $('.team-titlebutton-wrapper .active').removeClass('active');
    $(this).addClass(['active','clickable']);
});