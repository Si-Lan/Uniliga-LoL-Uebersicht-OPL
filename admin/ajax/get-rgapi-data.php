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
	$dbcn = create_dbcn();
	$ranks = array(
		"IRON IV" => 1,
		"IRON III" => 2,
		"IRON II" => 3,
		"IRON I" => 4,
		"BRONZE IV" => 5,
		"BRONZE III" => 6,
		"BRONZE II" => 7,
		"BRONZE I" => 8,
		"SILVER IV" => 9,
		"SILVER III" => 10,
		"SILVER II" => 11,
		"SILVER I" => 12,
		"GOLD IV" => 13,
		"GOLD III" => 14,
		"GOLD II" => 15,
		"GOLD I" => 16,
		"PLATINUM IV" => 17,
		"PLATINUM III" => 18,
		"PLATINUM II" => 19,
		"PLATINUM I" => 20,
		"EMERALD IV" => 21,
		"EMERALD III" => 22,
		"EMERALD II" => 23,
		"EMERALD I" => 24,
		"DIAMOND IV" => 25,
		"DIAMOND III" => 26,
		"DIAMOND II" => 27,
		"DIAMOND I" => 28,
		"MASTER" => 32,
		"GRANDMASTER" => 36,
		"CHALLENGER" => 39
	);
	$ranks_rev = array(
		1 => ["IRON", " IV"],
		2 => ["IRON", " III"],
		3 => ["IRON", " II"],
		4 => ["IRON", " I"],
		5 => ["BRONZE", " IV"],
		6 => ["BRONZE", " III"],
		7 => ["BRONZE", " II"],
		8 => ["BRONZE", " I"],
		9 => ["SILVER", " IV"],
		10 => ["SILVER", " III"],
		11 => ["SILVER", " II"],
		12 => ["SILVER", " I"],
		13 => ["GOLD", " IV"],
		14 => ["GOLD", " III"],
		15 => ["GOLD", " II"],
		16 => ["GOLD", " I"],
		17 => ["PLATINUM", " IV"],
		18 => ["PLATINUM", " III"],
		19 => ["PLATINUM", " II"],
		20 => ["PLATINUM", " I"],
		21 => ["EMERALD", " IV"],
		22 => ["EMERALD", " III"],
		23 => ["EMERALD", " II"],
		24 => ["EMERALD", " I"],
		25 => ["DIAMOND", " IV"],
		26 => ["DIAMOND", " III"],
		27 => ["DIAMOND", " II"],
		28 => ["DIAMOND", " I"],
		29 => ["MASTER",""],
		30 => ["MASTER",""],
		31 => ["MASTER",""],
		32 => ["MASTER",""],
		33 => ["MASTER",""],
		34 => ["MASTER",""],
		35 => ["GRANDMASTER",""],
		36 => ["GRANDMASTER",""],
		37 => ["GRANDMASTER",""],
		38 => ["CHALLENGER",""],
		39 => ["CHALLENGER",""]
	);

	$teamID = $_REQUEST["team"];
	$tournamentID = $_REQUEST["tournament"] ?? NULL;
	if ($tournamentID != NULL) {
		$players = $dbcn->execute_query("SELECT * FROM players p JOIN players_in_teams_in_tournament pit on p.OPL_ID = pit.OPL_ID_player WHERE OPL_ID_team = ? AND OPL_ID_tournament = ?", [$teamID, $tournamentID])->fetch_all(MYSQLI_ASSOC);
	} else {
		$players = $dbcn->execute_query("SELECT * FROM players p JOIN players_in_teams pit on p.OPL_ID = pit.OPL_ID_player WHERE OPL_ID_team = ?", [$teamID])->fetch_all(MYSQLI_ASSOC);
	}
	$rank_arr = [];
	foreach ($players as $player) {
		if ($player['rank_tier'] != NULL && $player['rank_tier'] != "UNRANKED") {
			$player_rank = 0;
			if ($player['rank_tier'] === "MASTER" || $player['rank_tier'] === "GRANDMASTER" || $player['rank_tier'] === "CHALLENGER") {
				$player_rank = $ranks[$player['rank_tier']];
			} else {
				$player_rank = $ranks[$player['rank_tier']." ".$player['rank_div']];
			}
			$rank_arr[] = $player_rank;
		}
	}
	if (count($rank_arr) == 0) {
		$dbcn->query("UPDATE teams SET avg_rank_tier = NULL, avg_rank_div = NULL, avg_rank_num = NULL WHERE OPL_ID = {$teamID}");
		echo "";
	} else {
		$rank = 0;
		foreach ($rank_arr as $player) {
			$rank += $player;
		}
		$rank_num = $rank / count($rank_arr);
		$rank = floor($rank_num);
		$dbcn->query("UPDATE teams SET avg_rank_tier = '{$ranks_rev[$rank][0]}', avg_rank_div = '{$ranks_rev[$rank][1]}', avg_rank_num = {$rank_num} WHERE OPL_ID = {$teamID}");
		echo $ranks_rev[$rank][0] . $ranks_rev[$rank][1] . " " . $rank_num;
	}
}


if ($type == "get-played-positions-for-players") {
	$teamID = $_REQUEST["team"];
	$tournamentID = $_REQUEST["tournament"];
	$result = get_played_positions_for_players($teamID, $tournamentID);
	echo $result["echo"];
}
if ($type == "get-played-champions-for-players") {
	$teamID = $_REQUEST["team"];
	$tournamentID = $_REQUEST["tournament"];
	$result = get_played_champions_for_players($teamID, $tournamentID);
	echo $result["echo"];
}
if ($type == "calculate-teamstats") {
	$teamID = $_REQUEST["team"];
	$tournamentID = $_REQUEST["tournament"];
	$result = calculate_teamstats($teamID, $tournamentID);
	echo $result["echo"];
}