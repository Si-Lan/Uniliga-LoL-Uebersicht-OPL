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
$team_groups = $dbcn->execute_query("SELECT * FROM teams JOIN teams_in_tournaments tit ON teams.OPL_ID = tit.OPL_ID_team WHERE OPL_ID = ? AND OPL_ID_group IN (SELECT OPL_ID FROM tournaments WHERE (eventType='group' OR (eventType = 'league' AND format = 'swiss') OR eventType='wildcard') AND OPL_ID_top_parent = ?) ORDER BY OPL_ID_group", [$teamID, $tournamentID])->fetch_all(MYSQLI_ASSOC);
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
if ($team_solo == NULL) {
	echo create_html_head_elements(title: "Team nicht gefunden | Uniliga LoL - Übersicht");
	echo "<body class='$lightmode'>";
	echo create_header(title: "error");
	echo "<div style='text-align: center'>Kein Team unter der angegebenen ID gefunden!</div></body>";
	exit();
}

$team = end($team_groups);

$group = $dbcn->execute_query("SELECT * FROM tournaments WHERE (eventType='group' OR (eventType = 'league' AND format = 'swiss') OR eventType='wildcard') AND OPL_ID = ?", [$team["OPL_ID_group"]])->fetch_assoc();
$league = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType='league' AND OPL_ID = ?", [$group["OPL_ID_parent"]])->fetch_assoc();
if ($group["format"] == "swiss" || $group["eventType"] == "wildcard") $league = $group;

$teams_from_groupDB = $dbcn->execute_query("SELECT * FROM teams JOIN teams_in_tournaments tit on teams.OPL_ID = tit.OPL_ID_team WHERE OPL_ID_group = ?", [$group["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
$teams_from_group = [];
foreach ($teams_from_groupDB as $i=>$team_from_group) {
	$teams_from_group[$team_from_group['OPL_ID']] = $team_from_group;
}

$team_name_now = $dbcn->execute_query("SELECT name FROM team_name_history WHERE OPL_ID_team = ? AND (update_time < ? OR ? IS NULL) ORDER BY update_time DESC", [$teamID,$tournament["dateEnd"],$tournament["dateEnd"]])->fetch_column();
$team["name"] = $team_name_now;

$t_name_clean = preg_replace("/LoL\s/i","",$tournament["name"]);
echo create_html_head_elements(css: ["game"], title: "{$team_name_now} - Matchhistory | $t_name_clean", loggedin: $logged_in);


?>
<body class="match-history <?php echo "$lightmode"?>">
<?php

$pageurl = $_SERVER['REQUEST_URI'];
$opl_tourn_url = "https://www.opleague.pro/event/";

echo create_header($dbcn,"tournament", $tournamentID);
echo create_tournament_nav_buttons($tournamentID, $dbcn,"",$league['OPL_ID'],$group['OPL_ID']);
echo create_team_nav_buttons($tournamentID,$group["OPL_ID"],$team,"matchhistory");
?>
<main>
<?php
if (count($team_groups)>1) {
	echo "<div id='teampage_switch_group_buttons'>";

	foreach ($team_groups as $team_group) {
		$group_details = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$team_group["OPL_ID_group"]])->fetch_assoc();
		if ($group_details["eventType"] == "group") {
			$league = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType = 'league' AND OPL_ID = ?", [$group_details["OPL_ID_parent"]])->fetch_assoc();
			$group_title = "Liga {$league['number']} - Gruppe {$group_details['number']}";
		} elseif ($group_details["eventType"] == "wildcard") {
			$wildcard_numbers_combined = ($group_details["numberRangeTo"] == null) ? $group_details["number"] : $group_details["number"]."-".$group_details["numberRangeTo"];
			$group_title = "Wildcard Liga ".$wildcard_numbers_combined;
		} elseif ($group_details["eventType"] == "league" && $group_details["format"] == "swiss") {
			$group_title = "Liga {$group_details['number']} Swiss-Gruppe";
		} else {
			$group_title = "Gruppe {$group_details['number']}";
		}
		$active_group = ($team_group["OPL_ID_group"] == $group["OPL_ID"]) ? "active" : "";
		echo "<button type='button' class='teampage_switch_group $active_group' data-group='{$team_group["OPL_ID_group"]}' data-team='$teamID' data-tournament='$tournamentID'>
                $group_title
              </button>";
	}

	echo "</div>";
}

create_matchhistory($dbcn, $tournamentID, $group["OPL_ID"], $teamID);

?>
</main>
</body>
</html>