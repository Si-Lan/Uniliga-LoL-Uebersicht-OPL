<?php
include_once __DIR__."/../setup/data.php";
include_once __DIR__."/../functions/fe-functions.php";

$pass = check_login();
?>
<!DOCTYPE html>
<html lang="de">
<?php
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
$groupID= $_GET["group"] ?? NULL;

$tournamentID = $tournament_url_path;
if (preg_match("/^(winter|sommer)([0-9]{2})$/",strtolower($tournamentID),$url_path_matches)) {
	$split = $url_path_matches[1];
	$season = $url_path_matches[2];
	$tournamentID = $dbcn->execute_query("SELECT OPL_ID FROM tournaments WHERE season = ? AND split = ? AND eventType = 'tournament'", [$season, $split])->fetch_column();
}
$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ? AND eventType = 'tournament'", [$tournamentID])->fetch_assoc();
$group = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ? AND eventType = 'group'", [$groupID])->fetch_assoc();
if ($group == null) {
    $group = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ? AND eventType = 'league' AND format = 'swiss'", [$groupID])->fetch_assoc();
}
$swiss = (($group["format"]??"") == 'swiss');

if ($tournament == NULL || $group == NULL) {
	echo create_html_head_elements(title: "Gruppe nicht gefunden | Uniliga LoL - Übersicht");
	echo "<body class='$lightmode'>";
	echo show_old_url_warning($tournamentID);
	echo create_header(title: "error");
	echo "<div style='text-align: center'>Keine Gruppe unter der angegebenen ID gefunden!</div></body>";
	exit();
}

if ($swiss) {
    $league = $group;
} else {
	$league = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ? AND eventType = 'league'", [$group["OPL_ID_parent"]])->fetch_assoc();
}

$t_name_clean = preg_replace("/LoL\s/","",$tournament["name"]);
if ($swiss) {
	echo create_html_head_elements(css: ["game"], title: "Liga {$league["number"]} - Swiss-Gruppe | $t_name_clean | Uniliga LoL - Übersicht");
} else {
	echo create_html_head_elements(css: ["game"], title: "Liga {$league["number"]} - Gruppe {$group["number"]} | $t_name_clean | Uniliga LoL - Übersicht");
}

$open_popup = "";
if (isset($_GET['match'])) {
	$open_popup = "popup_open";
}

?>
<body class="group <?php echo "$lightmode $open_popup"?>">
<?php

$pageurl = $_SERVER['REQUEST_URI'];
$opl_tourn_url = "https://www.opleague.pro/event/";

$matches = $dbcn->execute_query("SELECT * FROM matchups WHERE OPL_ID_tournament = ? ORDER BY playday",[$group['OPL_ID']])->fetch_all(MYSQLI_ASSOC);
$matches_grouped = [];
foreach ($matches as $match) {
	$matches_grouped[$match['playday']][] = $match;
}
$teams_from_groupDB = $dbcn->execute_query("SELECT * FROM teams JOIN teams_in_tournaments tit ON teams.OPL_ID = tit.OPL_ID_team WHERE tit.OPL_ID_group = ? ORDER BY standing",[$group['OPL_ID']])->fetch_all(MYSQLI_ASSOC);
$teams_from_group = [];
foreach ($teams_from_groupDB as $i=>$team_from_group) {
	$teams_from_group[$team_from_group['OPL_ID']] = array("name"=>$team_from_group['name'], "OPL_ID_logo"=>$team_from_group['OPL_ID_logo']);
}
if ($league["format"] === "swiss") {
	$group_title = "Swiss-Gruppe";
} else {
	$group_title = "Gruppe {$group['number']}";
}

echo create_header(dbcn: $dbcn, title: "tournament", tournament_id: $tournamentID);

echo create_tournament_nav_buttons($tournamentID, $dbcn,"group",$league['OPL_ID'],$groupID);

if (!$tournament["archived"]) {

	$last_user_update = $dbcn->execute_query("SELECT last_update FROM updates_user_group WHERE OPL_ID_group = ?", [$groupID])->fetch_column();
	$last_cron_update = $dbcn->execute_query("SELECT last_update FROM updates_cron WHERE OPL_ID_tournament = ?", [$tournamentID])->fetch_column();

	$last_update = max($last_user_update, $last_cron_update);

	if ($last_update == NULL) {
		$updatediff = "unbekannt";
	} else {
		$last_update = strtotime($last_update);
		$currtime = time();
		$updatediff = max_time_from_timestamp($currtime - $last_update);
	}
}

echo "<div class='pagetitlewrapper withupdatebutton'>
				<div class='pagetitle'>
					<h2 class='pagetitle'>Liga {$league['number']} - $group_title</h2>
                	<a href='$opl_tourn_url{$groupID}' target='_blank' class='toorlink'><div class='material-symbol'>".file_get_contents(__DIR__."/../icons/material/open_in_new.svg")."</div></a>
              	</div>";
if (!$tournament["archived"]) {
	echo "
              	<div class='updatebuttonwrapper'>
              		<button type='button' class='icononly user_update_group update_data' data-group='$groupID'><div class='material-symbol'>" . file_get_contents(__DIR__ . "/../icons/material/sync.svg") . "</div></button>
					<span>letztes Update:<br>$updatediff</span>
				</div>";
}
echo "
              </div>";

echo "<div class='main-content'>";

echo create_standings($dbcn,$tournamentID,$groupID);

echo "<div class='matches'>
                <div class='title'><h3>Spiele</h3></div>";

$curr_matchID = $_GET['match'] ?? NULL;
if ($curr_matchID != NULL) {
	$curr_matchData = $dbcn->execute_query("SELECT * FROM matchups WHERE OPL_ID = ?",[$curr_matchID])->fetch_assoc();
	$curr_games = $dbcn->execute_query("SELECT * FROM games g JOIN games_to_matches gtm on g.RIOT_matchID = gtm.RIOT_matchID WHERE OPL_ID_matches = ? ORDER BY g.RIOT_matchID",[$curr_matchID])->fetch_all(MYSQLI_ASSOC);
	$curr_team1 = $dbcn->execute_query("SELECT * FROM teams LEFT JOIN team_name_history tnh ON tnh.OPL_ID_team = teams.OPL_ID AND (update_time < ? OR ? IS NULL) WHERE OPL_ID = ? ORDER BY update_time DESC",[$tournament["dateEnd"],$tournament["dateEnd"],$curr_matchData['OPL_ID_team1']])->fetch_assoc();
	$curr_team2 = $dbcn->execute_query("SELECT * FROM teams LEFT JOIN team_name_history tnh ON tnh.OPL_ID_team = teams.OPL_ID AND (update_time < ? OR ? IS NULL) WHERE OPL_ID = ? ORDER BY update_time DESC",[$tournament["dateEnd"],$tournament["dateEnd"],$curr_matchData['OPL_ID_team2']])->fetch_assoc();

	if (!$tournament["archived"]) {
		$last_user_update_match = $dbcn->execute_query("SELECT last_update FROM updates_user_matchup WHERE OPL_ID_matchup = ?", [$curr_matchID])->fetch_column();

		$last_update_match = max($last_user_update_match,$last_cron_update);

		if ($last_update_match == NULL) {
			$updatediff_match = "unbekannt";
		} else {
			$last_update_match = strtotime($last_update_match);
			$currtime = time();
			$updatediff_match = max_time_from_timestamp($currtime-$last_update_match);
		}
	}

	echo "
                    <div class='mh-popup-bg' onclick='close_popup_match(event)' style='display: block; opacity: 1;'>
                        <div class='mh-popup'>
                            <div class='close-button' onclick='closex_popup_match()'><div class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/close.svg") ."</div></div>
                            <div class='close-button-space'></div>
                            <div class='mh-popup-buttons'>";
	if (!$tournament["archived"]) {
		echo "                      <div class='updatebuttonwrapper'><button type='button' class='icononly user_update_match update_data' data-match='$curr_matchID' data-matchformat='groups' data-group='$groupID'><div class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/sync.svg") ."</div></button><span>letztes Update:<br>$updatediff_match</span></div>";
	}
	echo "                  </div>";
	if ($curr_matchData['winner'] == $curr_matchData['OPL_ID_team1']) {
		$team1score = "win";
		$team2score = "loss";
	} elseif ($curr_matchData['winner'] == $curr_matchData['OPL_ID_team2']) {
		$team1score = "loss";
		$team2score = "win";
	} else {
		$team1score = "draw";
		$team2score = "draw";
	}
	echo "
                <h2 class='round-title'>
                    <span class='round'>Runde {$curr_matchData['playday']}: &nbsp</span>
                    <span class='team $team1score'>{$curr_team1['name']}</span>
                    <span class='score'><span class='$team1score'>{$curr_matchData['team1Score']}</span>:<span class='$team2score'>{$curr_matchData['team2Score']}</span></span>
                    <span class='team $team2score'>{$curr_team2['name']}</span>
                </h2>";
    if ($curr_games == null) {
        echo "<div class=\"no-game-found\">Keine Spieldaten gefunden</div>";
    }
	foreach ($curr_games as $game_i=>$curr_game) {
		echo "<div class='game game$game_i'>";
		$gameID = $curr_game['RIOT_matchID'];
		echo create_game($dbcn,$gameID,tournamentID: $tournamentID);
		echo "</div>";
	}
	echo "
                        </div>
                    </div>";
} else {
	echo "   <div class='mh-popup-bg' onclick='close_popup_match(event)'>
                            <div class='mh-popup'></div>
                     </div>";
}
echo "<div class='match-content content'>";
foreach ($matches_grouped as $roundNum=>$round) {
	echo "<div class='match-round'>
                    <h4>Runde $roundNum</h4>
                    <div class='divider'></div>
                    <div class='match-wrapper'>";
	foreach ($round as $match) {
		echo create_matchbutton($dbcn,$match['OPL_ID'],"groups",$tournamentID);
	}
	echo "</div>";
	echo "</div>"; // match-round
}
echo "</div>"; // match-content
echo "</div>"; // matches
echo "</div>"; // main-content

?>
</body>
</html>