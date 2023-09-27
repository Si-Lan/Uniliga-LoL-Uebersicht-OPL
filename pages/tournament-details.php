<!DOCTYPE html>
<html lang="de">
<?php
include_once(dirname(__FILE__) . "/../setup/data.php");
include_once(dirname(__FILE__)."/../functions/fe-functions.php");

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

$tournament_url_path = $_GET["tournament"] ?? NULL;
$tournamentID = $tournament_url_path;
if (preg_match("/^(winter|sommer)([0-9]{2})$/",strtolower($tournamentID),$url_path_matches)) {
	$split = $url_path_matches[1];
	$season = $url_path_matches[2];
	$tournamentID = $dbcn->execute_query("SELECT OPL_ID FROM tournaments WHERE season = ? AND split = ? AND eventType = 'tournament'", [$season, $split])->fetch_column();
}



$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ? AND eventType = 'tournament'", [$tournamentID])->fetch_assoc();

if ($tournament == NULL) {
	echo create_html_head_elements(title: "Kein Turnier gefunden | Uniliga LoL - Übersicht");
	echo "<body class='$lightmode'>";
	echo create_header(title: "error");
	echo "<div style='text-align: center'>Kein Turnier unter der angegebenen ID gefunden!</div></body>";
	exit();
}

$t_name_clean = preg_replace("/LoL/","",$tournament["name"]);
echo create_html_head_elements(title: "$t_name_clean | Uniliga LoL - Übersicht");

?>
<body class="tournament <?php echo $lightmode?> <?php echo $admin_btns;?>">
<?php

echo create_header($dbcn, title: "tournament", tournament_id: $tournamentID);

echo create_tournament_nav_buttons(tournament_id: $tournament_url_path, active: "overview");

$leagues = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType='league' AND deactivated = FALSE", [$tournamentID])->fetch_all(MYSQLI_ASSOC);

echo "<h2 class='pagetitle'>Turnier-Details</h2>";
echo "<div class='divisions-list-wrapper'>";
echo "<div class='divisions-list'>";

foreach ($leagues as $league) {
	echo "<div class='division'>
                        <div class='group-title-wrapper'><h2>Liga {$league['number']}</h2>";
	if ($logged_in) {
		echo "<a class='button write games-div {$league['OPL_ID']}'><div class='material-symbol'>". file_get_contents("../icons/material/place_item.svg") ."</div>Lade Spiele</a>";
	}
	echo "</div>";
	if ($logged_in) {
		echo "<div class='result-wrapper no-res {$league['OPL_ID']} {$tournamentID}'>
                            <div class='clear-button'>Clear</div>
                            <div class='result-content'></div>
                          </div>";
	}
	echo "<div class='divider'></div>";

	$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType = 'group' AND OPL_ID_parent = ? ORDER BY number", [$league["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);

	echo "<div class='groups'>";
	foreach ($groups as $group) {
		$group_title = "Gruppe {$group['number']}";

		echo "<div>";
		echo "<div class='group'>
                            <a href='turnier/{$tournament_url_path}/gruppe/{$group['OPL_ID']}' class='button'>$group_title</a>
                            <a href='turnier/{$tournament_url_path}/teams?liga={$league['OPL_ID']}&gruppe={$group['OPL_ID']}' class='button'><div class='material-symbol'>". file_get_contents("../icons/material/group.svg") ."</div>Teams</a>";
		echo "</div>"; // group
		if ($logged_in) {
			echo "<a class='button write games- {$group['OPL_ID']}'><div class='material-symbol'>". file_get_contents("../icons/material/place_item.svg") ."</div>Lade Spiele</a>";
		}
		echo "</div>";
		if ($logged_in) {
			echo "
                            <div class='result-wrapper no-res {$group['OPL_ID']} {$tournamentID}'>
                                <div class='clear-button'>Clear</div>
                                <div class='result-content'></div>
                            </div>";
		}
	}
	echo "</div>";

	echo "</div>";
}

echo "</div>";
echo "</div>";

$dbcn->close();
?>

</body>
</html>