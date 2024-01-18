<?php
include_once __DIR__."/../setup/data.php";
include_once __DIR__."/../functions/fe-functions.php";

$pass = check_login();
?>
<!DOCTYPE html>
<html lang="de">
<?php
$lightmode = is_light_mode(true);
$logged_in = is_logged_in();
$admin_btns = admin_buttons_visible(true);

try {
    $dbcn = create_dbcn();
} catch (Exception $e) {
    echo create_html_head_elements(title: "Error");
    echo "<body class='$lightmode'>";
    echo create_header(title: "error");
    echo "<div style='text-align: center'>Database Connection failed</div></body>";
    exit();
}

echo create_html_head_elements(title: "Spielersuche | Uniliga LoL - Übersicht", loggedin: $logged_in);

?>
<body class="players <?php echo "$lightmode $admin_btns"?>">
<?php

echo create_header(dbcn: $dbcn, title: "players");

echo "<div class='main-content'>";
echo "<div><h2>Spielersuche</h2>Suche nach Spielernamen oder RiotID</div>";
echo "<div class='search-wrapper'>
                <span class='searchbar'>
                    <input class=\"search-players deletable-search\" placeholder='Spieler suchen' type='text'>
                    <a class='material-symbol clear-search' href='#'>". file_get_contents(dirname(__FILE__)."/../icons/material/close.svg") ."</a>
                </span>
              </div>";
echo "
            <div class='player-popup-bg' onclick='close_popup_player(event)'>
                <div class='player-popup'></div>
            </div>";
echo "<div class='recent-players-list'></div>";
echo "<div class='player-list'></div>";
echo "</div>";

?>
</body>
</html>
