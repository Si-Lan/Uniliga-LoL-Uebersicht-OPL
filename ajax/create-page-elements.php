<?php
include_once __DIR__."/../setup/data.php";
include_once __DIR__."/../functions/fe-functions.php";
include_once __DIR__."/../functions/helper.php";
include_once __DIR__."/../functions/summoner-card.php";

$dbcn = create_dbcn();

$type = $_SERVER["HTTP_TYPE"] ?? $_REQUEST["type"] ?? NULL;
if ($type == NULL) exit;

if ($type == "standings") {
	$group_ID = $_SERVER["HTTP_GROUPID"] ?? $_REQUEST['group'] ?? NULL;
	$team_ID = $_SERVER["HTTP_TEAMID"] ?? $_REQUEST['team'] ?? NULL;
	if ($group_ID == NULL) {
		exit;
	}

	$group = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$group_ID])->fetch_assoc();
	if ($group["eventType"] != "group") exit;
	$tourn_ID = $dbcn->execute_query("SELECT OPL_ID_parent FROM tournaments WHERE eventType='league' AND OPL_ID = ?", [$group["OPL_ID_parent"]])->fetch_column();
	echo create_standings($dbcn, $tourn_ID, $group_ID, $team_ID);
}
if ($type == "matchbutton") {
	$match_ID = $_SERVER["HTTP_MATCHID"] ?? $_REQUEST['match'] ?? NULL;
	$team_ID = $_SERVER["HTTP_TEAMID"] ?? $_REQUEST['team'] ?? NULL;
	$tournament_ID = $_SERVER["HTTP_TOURNAMENTID"] ?? $_REQUEST['tournament'] ?? NULL;
	$matchtype = $_SERVER["HTTP_MATCHTYPE"] ?? $_REQUEST['mtype'] ?? 'groups';

	$group_ID = $dbcn->execute_query("SELECT OPL_ID_tournament FROM matchups WHERE OPL_ID = ?", [$match_ID])->fetch_column();
	echo create_matchbutton($dbcn, $match_ID, $matchtype, $team_ID, $tournament_ID);
}
if ($type == "summoner-card-container") {
	$team_ID = $_SERVER['HTTP_TEAMID'] ?? $_REQUEST["team"] ?? NULL;
	$tourn_ID = $_SERVER['HTTP_TOURNAMENTID'] ?? $_REQUEST["tournament"] ?? NULL;
	if ($team_ID == NULL) exit();

	if ($tourn_ID != NULL){
		$players = $dbcn->execute_query("SELECT * FROM players p JOIN players_in_teams_in_tournament pit on p.OPL_ID = pit.OPL_ID_player LEFT JOIN stats_players_teams_tournaments spit on p.OPL_ID = spit.OPL_ID_player AND pit.OPL_ID_team = spit.OPL_ID_team AND pit.OPL_ID_tournament = spit.OPL_ID_tournament WHERE pit.OPL_ID_team = ? AND (pit.OPL_ID_tournament = ? OR pit.OPL_ID_tournament IN (SELECT OPL_ID_parent FROM tournaments WHERE eventType='league' AND OPL_ID = ?) OR pit.OPL_ID_tournament IN (SELECT OPL_ID_parent FROM tournaments WHERE eventType='league' AND OPL_ID IN (SELECT OPL_ID_parent FROM tournaments WHERE eventType='group' AND OPL_ID = ?)))", [$team_ID, $tourn_ID, $tourn_ID, $tourn_ID])->fetch_all(MYSQLI_ASSOC);
	} else {
		$players = $dbcn->execute_query("SELECT * FROM players p JOIN players_in_teams pit on p.OPL_ID = pit.OPL_ID_player LEFT JOIN stats_players_teams_tournaments spit on p.OPL_ID = spit.OPL_ID_player AND pit.OPL_ID_team = spit.OPL_ID_team WHERE pit.OPL_ID_team = ?", [$team_ID])->fetch_all(MYSQLI_ASSOC);
	}

	$players_gamecount_by_id = array();
	foreach ($players as $player) {
		$played_games = 0;
		if ($player['roles'] == NULL) {
			$players_gamecount_by_id[$player['OPL_ID']] = $played_games;
			continue;
		}
		foreach (json_decode($player['roles'], true) as $role_played_amount) {
			$played_games += $role_played_amount;
		}
		$players_gamecount_by_id[$player['OPL_ID']] = $played_games;
	}
	arsort($players_gamecount_by_id);
	$collapsed = summonercards_collapsed();
	echo "<div class='summoner-card-container'>";
	foreach ($players_gamecount_by_id as $player_id => $player_gamecount) {
		echo create_summonercard($dbcn, $player_id, $tourn_ID, $team_ID, $collapsed);
	}
	echo "</div>";
}
$dbcn->close();