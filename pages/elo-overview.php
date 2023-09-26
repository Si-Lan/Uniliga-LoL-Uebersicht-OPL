<!DOCTYPE html>
<html lang="de">
<?php
include_once(dirname(__FILE__) . "/../setup/data.php");
include_once(dirname(__FILE__)."/../functions/fe-functions.php");

$lightmode = is_light_mode(true);

try {
	$dbcn = create_dbcn();
} catch (Exception $e) {
	echo create_html_head_elements(title: "Error");
	echo "<body class='$lightmode'>";
	echo create_header(title: "error");
	echo "<div style='text-align: center'>Database Connection failed</div></body>";
	exit();
}

$tournament_url_path = $_GET["tournament"] ?? NULL;
$tournamentID = $tournament_url_path;
if (preg_match("/^(winter|sommer)([0-9]{2})$/",strtolower($tournamentID),$url_path_matches)) {
	$split = $url_path_matches[1];
	$season = $url_path_matches[2];
	$tournamentID = $dbcn->execute_query("SELECT OPL_ID FROM tournaments WHERE season = ? AND split = ? AND eventType = 'tournament'", [$season, $split])->fetch_column();
}

$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournamentID])->fetch_assoc();

if ($tournament == NULL) {
	echo create_html_head_elements(title: "Kein Turnier gefunden | Uniliga LoL - Übersicht");
	echo "<body class='$lightmode'>";
	echo create_header(title: "error");
	echo "<div style='text-align: center'>Kein Turnier unter der angegebenen ID gefunden!</div></body>";
	exit();
}

$t_name_clean = preg_replace("/LoL/","",$tournament["name"]);
echo create_html_head_elements(title: "Elo-Übersicht - $t_name_clean | Uniliga LoL - Übersicht");

?>
<body class="teamlist <?php echo $lightmode?>">
<?php

echo create_header($dbcn, title: "tournament", tournament_id: $tournamentID);

echo create_tournament_nav_buttons(tournament_id: $tournament_url_path, active: "elo");

?>

</body>
</html>