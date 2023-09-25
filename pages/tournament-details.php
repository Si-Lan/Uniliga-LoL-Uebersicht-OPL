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

$tournamentID_path = $_GET["tournament"] ?? NULL;


$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournamentID_path])->fetch_assoc();

if ($tournament == NULL) {
	echo create_html_head_elements(title: "Kein Turnier gefunden | Uniliga LoL - Übersicht");
	echo "<body class='$lightmode'>";
	echo create_header(title: "error");
	echo "<div style='text-align: center'>Kein Turnier unter der angegebenen ID gefunden!</div></body>";
	exit();
}

$t_name_clean = preg_replace("/LoL/","",$tournament["name"]);
echo create_html_head_elements(title: $t_name_clean);

?>
<body class="tournament <?php echo $lightmode?>">
<?php

echo create_header($dbcn, title: "tournament", tournament_id: $tournamentID_path);

echo create_tournament_nav_buttons(tournament_id: $tournamentID_path, active: "overview");

// TODO: hier abfragen:
// --top-turnier listet verknüpfte Ligen und deren Gruppen
// --Liga listet Gruppen und verknüpfte Ligen
// --Gruppe sollte group-details auflisten, hmm

?>

</body>
</html>