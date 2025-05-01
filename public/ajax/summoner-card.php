<?php
include_once dirname(__DIR__,2)."/config/data.php";
include_once dirname(__DIR__,2)."/src/functions/summoner-card.php";
include_once dirname(__DIR__,2)."/src/functions/helper.php";

$dbcn = create_dbcn();

$tournamentID = $_SERVER['HTTP_TOURNAMENTID'] ?? NULL;
$teamID = $_SERVER['HTTP_TEAMID'] ?? NULL;

$playerID = $_SERVER['HTTP_PLAYERID'] ?? NULL;
if ($playerID != NULL) {
	echo create_summonercard($dbcn, $playerID, $tournamentID, $teamID);
	exit;
}

if ($teamID != NULL) {
	$players = $dbcn->execute_query("SELECT * FROM players JOIN players_in_teams_in_tournament pit ON players.OPL_ID = pit.OPL_ID_player AND pit.OPL_ID_tournament = ? LEFT JOIN stats_players_teams_tournaments spit ON pit.OPL_ID_player = spit.OPL_ID_player AND spit.OPL_ID_team = pit.OPL_ID_team AND spit.OPL_ID_tournament = pit.OPL_ID_tournament WHERE pit.OPL_ID_team = ? ", [$tournamentID, $teamID])->fetch_all(MYSQLI_ASSOC);
	$players_gamecount_by_id = array();
	foreach ($players as $player) {
		$played_games = 0;
		if ($player['roles'] == NULL) {
			$players_gamecount_by_id[$player['OPL_ID']] = $played_games;
			continue;
		}
		foreach (json_decode($player['roles'],true) as $role_played_amount) {
			$played_games += $role_played_amount;
		}
		$players_gamecount_by_id[$player['OPL_ID']] = $played_games;
	}
	arsort($players_gamecount_by_id);
	$cards = array();
	foreach ($players_gamecount_by_id as $player_id=>$player_gamecount) {
		$cards[] = create_summonercard($dbcn, $player_id, $tournamentID, $teamID, summonercards_collapsed());
	}
	echo json_encode($cards);
	exit;
}

$dbcn->close();