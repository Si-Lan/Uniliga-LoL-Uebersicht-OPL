<?php
include_once __DIR__."/../setup/data.php";
include_once __DIR__."/../admin/functions/get-opl-data.php";
include_once __DIR__."/../admin/functions/get-rgapi-data.php";

$type = $_SERVER['HTTP_TYPE'] ?? $_REQUEST["type"] ?? NULL;

if ($type == "update_start_time") {
	$dbcn = create_dbcn();
	$update_type = $_SERVER['HTTP_UPDATETYPE'] ?? $_REQUEST['utype'] ?? NULL;
	$item_ID = $_SERVER['HTTP_ITEMID'] ?? $_REQUEST['id'] ?? NULL;
	if ($item_ID == NULL || $update_type == NULL) exit;
	if ($update_type == "group") {
		$lastupdate = $dbcn->execute_query("SELECT * FROM updates_user_group WHERE OPL_ID_group = ?", [$item_ID])->fetch_assoc();
	} elseif ($update_type == "team") {
		$lastupdate = $dbcn->execute_query("SELECT * FROM updates_user_team WHERE OPL_ID_team = ?", [$item_ID])->fetch_assoc();
	} elseif ($update_type == "match") {
		$lastupdate = $dbcn->execute_query("SELECT * FROM updates_user_matchup WHERE OPL_ID_matchup = ?", [$item_ID])->fetch_assoc();
	} else {
		exit;
	}
	$t = date('Y-m-d H:i:s');
	if ($lastupdate == NULL) {
		if ($update_type == "group") {
			$dbcn->execute_query("INSERT INTO updates_user_group VALUES (?, ?)", [$item_ID, $t]);
		} elseif ($update_type == "team") {
			$dbcn->execute_query("INSERT INTO updates_user_team VALUES (?, ?)", [$item_ID, $t]);
		} elseif ($update_type == "match") {
			$dbcn->execute_query("INSERT INTO updates_user_matchup VALUES (?, ?)", [$item_ID, $t]);
		}
	} else {
		if ($update_type == "group") {
			$dbcn->execute_query("UPDATE updates_user_group SET last_update = ? WHERE OPL_ID_group = ? ", [$t, $item_ID]);
		} elseif ($update_type == "team") {
			$dbcn->execute_query("UPDATE updates_user_team SET last_update = ? WHERE OPL_ID_team = ? ", [$t, $item_ID]);
		} elseif ($update_type == "match") {
			$dbcn->execute_query("UPDATE updates_user_matchup SET last_update = ? WHERE OPL_ID_matchup = ? ", [$t, $item_ID]);
		}
	}
	$dbcn->close();
	exit("1");
}

if ($type == "teams_from_group") {
	$dbcn = create_dbcn();
	$groupID = $_SERVER['HTTP_GROUPID'] ?? NULL;

	$delete = FALSE;
	if (isset($_SERVER['HTTP_DELETETEAMS'])) {
		$delete = TRUE;
	}

	$teams = get_teams_for_tournament($groupID);

	echo json_encode($teams);

	$dbcn->close();
}

if ($type == "matchups_from_group") {
	$dbcn = create_dbcn();
	$groupID = $_SERVER['HTTP_GROUPID'] ?? NULL;

	$matchups = get_matchups_for_tournament($groupID);
	echo json_encode($matchups);
	$dbcn->close();
}

if ($type == "matchresult") {
	$dbcn = create_dbcn();
	$matchid = $_SERVER["HTTP_MATCHID"] ?? NULL;

	$result = get_results_for_matchup($matchid);
	echo json_encode($result);
	$dbcn->close();
}

if ($type == "calculate_standings") {
	$dbcn = create_dbcn();
	$groupID = $_SERVER['HTTP_GROUPID'] ?? NULL;

	$result = calculate_standings_from_matchups($groupID);
	echo json_encode($result);
	$dbcn->close();
}

if ($type == "players_in_team") {
	$dbcn = create_dbcn();
	$team_ID = $_SERVER['HTTP_TEAMID'] ?? NULL;

	$result = get_players_for_team($team_ID);
	echo json_encode($result);
	$dbcn->close();
}

if ($type == "summoner_for_player") {
	$dbcn = create_dbcn();
	$playerID = $_SERVER['HTTP_PLAYERID'] ?? NULL;

	$result = get_summonerNames_for_player($playerID);
	echo json_encode($result);
	$dbcn->close();
}

if ($type == "recalc_team_stats") {
	$dbcn = create_dbcn();
	$teamID = $_SERVER['HTTP_TEAMID'] ?? NULL;
	$tournamentID = $_SERVER['HTTP_TOURNAMENTID'] ?? NULL;

	get_played_champions_for_players($teamID,$tournamentID);
	get_played_positions_for_players($teamID,$tournamentID);
	calculate_teamstats($teamID,$tournamentID);

	echo "1";
	$dbcn->close();
}