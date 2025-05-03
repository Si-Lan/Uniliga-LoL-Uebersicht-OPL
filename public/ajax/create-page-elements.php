<?php
require_once dirname(__DIR__,2)."/src/autoload.php";
use App\Components\SummonerCard;

include_once dirname(__DIR__,2)."/config/data.php";
include_once dirname(__DIR__,2)."/src/functions/fe-functions.php";
include_once dirname(__DIR__,2)."/src/functions/helper.php";
include_once dirname(__DIR__,2)."/src/functions/summoner-card.php";

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
	if (!($group["eventType"] == "group" || ($group["eventType"] == "league" && $group["format"] == "swiss") || $group["eventType"] == "wildcard")) exit;
	$tourn_ID = $group["OPL_ID_top_parent"];
	echo create_standings($dbcn, $tourn_ID, $group_ID, $team_ID);
}
if ($type == "matchbutton") {
	$match_ID = $_SERVER["HTTP_MATCHID"] ?? $_REQUEST['match'] ?? NULL;
	$team_ID = $_SERVER["HTTP_TEAMID"] ?? $_REQUEST['team'] ?? NULL;
	$tournament_ID = $_SERVER["HTTP_TOURNAMENTID"] ?? $_REQUEST['tournament'] ?? NULL;
	$matchtype = $_SERVER["HTTP_MATCHTYPE"] ?? $_REQUEST['mtype'] ?? 'groups';

	$group_ID = $dbcn->execute_query("SELECT OPL_ID_tournament FROM matchups WHERE OPL_ID = ?", [$match_ID])->fetch_column();
	echo create_matchbutton($dbcn, $match_ID, $matchtype, $tournament_ID,  $team_ID);
}
if ($type == "matchbutton-list-group") {
	$group_ID = $_SERVER["HTTP_GROUPID"] ?? $_REQUEST["group"] ?? NULL;
	$group  = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$group_ID])->fetch_assoc();
	$tournament_ID = $group["OPL_ID_top_parent"];

	if ($group["format"] == "double-elim" || $group["format"] == "single-elim") {
		$matches = $dbcn->execute_query("
                                        SELECT *
                                        FROM matchups
                                        WHERE OPL_ID_tournament = ?
                                          AND NOT ((OPL_ID_team1 IS NULL || matchups.OPL_ID_team1 < 0) AND (OPL_ID_team2 IS NULL OR OPL_ID_team2 < 0))
                                        ORDER BY plannedDate",[$group_ID])->fetch_all(MYSQLI_ASSOC);
		$matches_grouped = [];
		foreach ($matches as $match) {
			if ($match['plannedDate'] == null) continue;
			$plannedDate = new DateTime($match['plannedDate']);
			$plannedDay = $plannedDate->format("Y-m-d H");
			$matches_grouped[$plannedDay][] = $match;
		}
	} else {
		$matches = $dbcn->execute_query("SELECT * FROM matchups WHERE OPL_ID_tournament = ? ORDER BY playday",[$group_ID])->fetch_all(MYSQLI_ASSOC);
		$matches_grouped = [];
		foreach ($matches as $match) {
			$matches_grouped[$match['playday']][] = $match;
		}
	}

	echo "<div class='match-content content'>";
	foreach ($matches_grouped as $roundNum=>$round) {
		echo "<div class='match-round'>
                    <h4>Runde $roundNum</h4>
                    <div class='divider'></div>
                    <div class='match-wrapper'>";
		foreach ($round as $match) {
			echo create_matchbutton($dbcn,$match['OPL_ID'],"groups",$tournament_ID);
		}
		echo "</div>";
		echo "</div>"; // match-round
	}
	echo "</div>"; // match-content
}
if ($type == "matchbutton-list-team") {
	$team_ID = $_SERVER["HTTP_TEAMID"] ?? $_REQUEST['team'] ?? NULL;
	$group_ID = $_SERVER["HTTP_GROUPID"] ?? $_REQUEST['group'] ?? NULL;
	$playoff_ID = $_SERVER["HTTP_PLAYOFFID"] ?? $_REQUEST['playoff'] ?? NULL;

	$matches = $dbcn->execute_query("SELECT * FROM matchups WHERE OPL_ID_tournament = ? AND (OPL_ID_team1 = ? OR OPL_ID_team2 = ?)", [$group_ID,$team_ID,$team_ID])->fetch_all(MYSQLI_ASSOC);
	$group = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$group_ID])->fetch_assoc();
	$tournament_ID = $group["OPL_ID_top_parent"];
	$matchtype = $group["eventType"];

	echo "<div class='match-content content'>";
	foreach ($matches as $match) {
		echo create_matchbutton($dbcn,$match['OPL_ID'],$matchtype,$tournament_ID,$team_ID);
	}

	$matches_playoffs = $dbcn->execute_query("SELECT * FROM matchups WHERE OPL_ID_tournament = ? AND (OPL_ID_team1 = ? OR OPL_ID_team2 = ?)", [$playoff_ID,$team_ID,$team_ID])->fetch_all(MYSQLI_ASSOC);

	if ($matches_playoffs != null && count($matches_playoffs) > 0) {
		echo "<h4>Playoffs</h4>";
	}
	foreach ($matches_playoffs as $match) {
		echo create_matchbutton($dbcn,$match['OPL_ID'],"playoffs",$tournament_ID,$team_ID);
	}

	echo "</div>";
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
		$summonerCard = new SummonerCard($dbcn,$player_id,$tourn_ID,$team_ID);
	}
	echo "</div>";
}

if ($type == "matchhistory") {
	$team_ID = $_SERVER["HTTP_TEAMID"] ?? $_REQUEST['team'] ?? NULL;
	$group_ID = $_SERVER["HTTP_GROUPID"] ?? $_REQUEST['group'] ?? NULL;
	$tournament_ID = $_SERVER["HTTP_TOURNAMENTID"] ?? $_REQUEST['tournament'] ?? NULL;

	create_matchhistory($dbcn, $tournament_ID, $group_ID, $team_ID);
}

$dbcn->close();