<?php
/** @var mysqli $dbcn  */

$playerID = $_GET["player"] ?? NULL;

$player = $dbcn->execute_query("SELECT * FROM players WHERE OPL_ID = ?", [$playerID])->fetch_assoc();

if ($player == NULL) {
    $_GET["error"] = "404";
    $_GET["404type"] = "player";
    require 'error.php';
    echo "</html>";
    exit();
}

echo create_html_head_elements(title: "{$player["name"]} | Uniliga LoL - Übersicht", loggedin: is_logged_in());

?>
<body class="player <?=is_light_mode(true)?>">
<?php

echo create_header(dbcn: $dbcn, title: "player");

echo "<div class='main-content'>";
echo create_player_overview($dbcn,$playerID, true);
echo "</div>";

?>
</body>