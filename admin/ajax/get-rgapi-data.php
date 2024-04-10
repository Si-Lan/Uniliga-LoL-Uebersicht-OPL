<?php
include_once __DIR__.'/../functions/get-rgapi-data.php';
include_once __DIR__."/../../setup/data.php";

$type = $_SERVER["HTTP_TYPE"] ?? $_REQUEST["type"];

if ($type == "puuids-by-team") {
	$teamID = $_SERVER["HTTP_TEAM"] ?? $_REQUEST['team'];

	if (isset($_REQUEST["all"])) {
		$results = get_puuids_by_team($teamID,TRUE);
	} else {
		$results = get_puuids_by_team($teamID);
	}

	echo json_encode($results, JSON_UNESCAPED_SLASHES);
}

if ($type == "riotid_for_player") {
	$playerID = $_SERVER["HTTP_PLAYER"] ?? $_REQUEST['player'];

	$results = get_riotid_for_player_by_puuid($playerID);

	echo json_encode($results, JSON_UNESCAPED_SLASHES);
}

if ($type == "games-by-player") {
	$playerID = $_SERVER["HTTP_PLAYERID"] ?? $_REQUEST['player'] ?? NULL;
	$tournamentID = $_SERVER["HTTP_TOURNAMENTID"] ?? $_REQUEST['tournament'] ?? NULL;

	$results = get_games_by_player($playerID, $tournamentID);

	$returnArr = $results["echo"];
	echo $returnArr;
}

if ($type == "add-match-data" || $type == "matchdata-and-assign") {
	$matchID = $_SERVER["HTTP_MATCHID"] ?? $_REQUEST['match'];
	$tournamentID = $_SERVER["HTTP_TOURNAMENTID"] ?? $_REQUEST['tournament'];

	$results = add_match_data($matchID, $tournamentID);

	$returnArr = $results["echo"];
	echo $returnArr;
}

if ($type == "assign-and-filter" || $type == "matchdata-and-assign") {
	$matchID = $_SERVER["HTTP_MATCHID"] ?? $_REQUEST['match'];
	$tournamentID = $_SERVER["HTTP_TOURNAMENTID"] ?? $_REQUEST['tournament'];

	$results = assign_and_filter_game($matchID, $tournamentID);

	$returnArr = $results["echo"];
	echo $returnArr;
}

if ($type == "get-rank-for-player") {
	$id = $_REQUEST['player'];

	$results = get_Rank_by_SummonerId($id);

	$returnArr = $results['echo'];
	echo $returnArr;
}

if ($type == "calculate-write-avg-rank") {
	$teamID = $_REQUEST["team"];
	$tournamentID = $_REQUEST["tournament"] ?? NULL;
	$result = calculate_avg_team_rank($teamID,$tournamentID);
	$result_2 = calculate_avg_team_rank($teamID);
	echo $result["echo"].$result_2["echo"];
}


if ($type == "get-stats-for-players") {
	$teamID = $_SERVER["HTTP_TEAM"] ?? $_REQUEST["team"];
	$tournamentID = $_SERVER["HTTP_TOURNAMENT"] ?? $_REQUEST["tournament"];
	$result = get_stats_for_players($teamID, $tournamentID);
	echo $result["echo"];
}
if ($type == "calculate-teamstats") {
	$teamID = $_REQUEST["team"];
	$tournamentID = $_REQUEST["tournament"];
	$result = calculate_teamstats($teamID, $tournamentID);
	echo $result["echo"];
}