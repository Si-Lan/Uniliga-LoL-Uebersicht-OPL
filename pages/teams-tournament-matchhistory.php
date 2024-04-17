<?php
include_once __DIR__."/../setup/data.php";
include_once __DIR__."/../functions/fe-functions.php";

check_login();
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

$tournament_url_path = $_GET["tournament"] ?? NULL;
$teamID = $_GET["team"] ?? NULL;

$tournamentID = $tournament_url_path;
if (preg_match("/^(winter|sommer)([0-9]{2})$/",strtolower($tournamentID),$url_path_matches)) {
	$split = $url_path_matches[1];
	$season = $url_path_matches[2];
	$tournamentID = $dbcn->execute_query("SELECT OPL_ID FROM tournaments WHERE season = ? AND split = ? AND eventType = 'tournament'", [$season, $split])->fetch_column();
}
$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ? AND eventType = 'tournament'", [$tournamentID])->fetch_assoc();
$team_groups = $dbcn->execute_query("SELECT * FROM teams JOIN teams_in_tournaments tit ON teams.OPL_ID = tit.OPL_ID_team WHERE OPL_ID = ? AND OPL_ID_group IN (SELECT OPL_ID FROM tournaments WHERE eventType='group' AND OPL_ID_parent IN (SELECT OPL_ID FROM tournaments WHERE eventType='league' AND OPL_ID_parent = ?))", [$teamID, $tournamentID])->fetch_all(MYSQLI_ASSOC);
$team_solo = $dbcn->execute_query("SELECT * FROM teams WHERE OPL_ID = ?", [$teamID])->fetch_assoc();

if ($tournament == NULL) {
	echo create_html_head_elements(title: "Turnier nicht gefunden | Uniliga LoL - Übersicht");
	echo "<body class='$lightmode'>";
	echo create_header(title: "error");
	echo "<div style='text-align: center'>Kein Turnier unter der angegebenen ID gefunden!</div></body>";
	exit();
}
if ($team_groups == NULL && $team_solo != NULL) {
	echo create_html_head_elements(title: "Team nicht im Turnier | Uniliga LoL - Übersicht");
	echo "<body class='$lightmode'>";
	echo create_header(title: "error");
	echo "<div style='text-align: center'>Dieses Team spielt nicht im angegebenen Turnier!</div><div style='display: flex; flex-direction: column; align-items: center;'><a class='button' href='team/$teamID'>zur Team-Seite</a></div></body>";
	exit();
}
if ($team_groups == NULL && $team_solo == NULL) {
	echo create_html_head_elements(title: "Team nicht gefunden | Uniliga LoL - Übersicht");
	echo "<body class='$lightmode'>";
	echo create_header(title: "error");
	echo "<div style='text-align: center'>Kein Team unter der angegebenen ID gefunden!</div></body>";
	exit();
}

// TODO: möglichkeit zwischen gruppen zu wechseln hinzufügen
if (count($team_groups) > 1) {
	// TODO: prüfen welche gruppe initial aufgerufen wurde
	$team = $team_groups[0];
} else {
	$team = $team_groups[0];
}

$group = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType='group' AND OPL_ID = ?", [$team["OPL_ID_group"]])->fetch_assoc();
$league = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType='league' AND OPL_ID = ?", [$group["OPL_ID_parent"]])->fetch_assoc();

$teams_from_groupDB = $dbcn->execute_query("SELECT * FROM teams JOIN teams_in_tournaments tit on teams.OPL_ID = tit.OPL_ID_team WHERE OPL_ID_group = ?", [$group["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
$teams_from_group = [];
foreach ($teams_from_groupDB as $i=>$team_from_group) {
	$teams_from_group[$team_from_group['OPL_ID']] = $team_from_group;
}

$team_name_now = $dbcn->execute_query("SELECT name FROM team_name_history WHERE OPL_ID_team = ? AND update_time > ? AND (update_time < ? OR ? IS NULL) ORDER BY update_time DESC", [$teamID,$tournament["dateStart"],$tournament["dateEnd"],$tournament["dateEnd"]])->fetch_column();
$team["name"] = $team_name_now;

$t_name_clean = preg_replace("/LoL\s/","",$tournament["name"]);
echo create_html_head_elements(css: ["game"], title: "{$team_name_now} - Matchhistory | $t_name_clean", loggedin: $logged_in);


?>
<body class="match-history <?php echo "$lightmode"?>">
<?php

$pageurl = $_SERVER['REQUEST_URI'];
$opl_tourn_url = "https://www.opleague.pro/event/";

echo create_header($dbcn,"tournament", $tournamentID);
echo create_tournament_nav_buttons($tournamentID, $dbcn,"",$league['OPL_ID'],$group['OPL_ID']);
echo create_team_nav_buttons($tournamentID,$group["OPL_ID"],$team,"matchhistory");

$matches = $dbcn->execute_query("SELECT * FROM matchups WHERE OPL_ID_tournament = ? AND (OPL_ID_team1 = ? OR OPL_ID_team2 = ?) AND played IS TRUE", [$group["OPL_ID"],$teamID,$teamID])->fetch_all(MYSQLI_ASSOC);

foreach ($matches as $m=>$match) {
	$games = $dbcn->execute_query("SELECT * FROM games g JOIN games_to_matches gtm on g.RIOT_matchID = gtm.RIOT_matchID WHERE OPL_ID_matches = ? ORDER BY g.RIOT_matchID",[$match['OPL_ID']])->fetch_all(MYSQLI_ASSOC);
	$team1 = $teams_from_group[$match['OPL_ID_team1']];
	$team2 = $teams_from_group[$match['OPL_ID_team2']];
	$team1name = $dbcn->execute_query("SELECT * FROM team_name_history WHERE OPL_ID_team = ? AND update_time > ? AND (update_time < ? OR ? IS NULL) ORDER BY update_time DESC", [$team1["OPL_ID"],$tournament["dateStart"],$tournament["dateEnd"],$tournament["dateEnd"]])->fetch_assoc();
	$team2name = $dbcn->execute_query("SELECT * FROM team_name_history WHERE OPL_ID_team = ? AND update_time > ? AND (update_time < ? OR ? IS NULL) ORDER BY update_time DESC", [$team2["OPL_ID"],$tournament["dateStart"],$tournament["dateEnd"],$tournament["dateEnd"]])->fetch_assoc();

	if ($match['winner'] == $match['OPL_ID_team1']) {
		$team1score = "win";
		$team2score = "loss";
	} elseif ($match['winner'] == $match['OPL_ID_team2']) {
		$team1score = "loss";
		$team2score = "win";
	} else {
		$team1score = "draw";
		$team2score = "draw";
	}
	if ($m != 0) {
		echo "<div class='divider rounds'></div>";
	}
	echo "<div id='{$match['OPL_ID']}' class='round-wrapper'>";
	echo "
                <h2 class='round-title'>
                    <span class='round'>Runde {$match['playday']}: &nbsp</span>
                    <span class='team $team1score'>{$team1name['name']}</span>
                    <span class='score'><span class='$team1score'>{$match['team1Score']}</span>:<span class='$team2score'>{$match['team2Score']}</span></span>
                    <span class='team $team2score'>{$team2name['name']}</span>
                </h2>";
	if ($games == NULL) {
		echo "</div>";
		continue;
	}
	foreach ($games as $game) {
		$gameID = $game['RIOT_matchID'];
		echo create_game($dbcn,$gameID,$teamID,tournamentID: $tournamentID);
	}
	echo "</div>";
}

?>
</body>
</html>