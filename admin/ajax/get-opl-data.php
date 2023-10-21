<?php
$root = __DIR__."/../../";
include_once $root."admin/functions/get-opl-data.php";
include_once $root."setup/data.php";

$type = $_SERVER["HTTP_TYPE"] ?? NULL;
if ($type == NULL) exit;

// gets the given tournament from OPL (1 Call to OPL-API)
if ($type == "get_tournament") {
	$id = $_SERVER["HTTP_ID"] ?? NULL;
	if (strlen($id) == 0) {
		echo "{}";
		exit;
	}
	$result = get_tournament($id);
	echo json_encode($result);
}

// adds the given tournament to DB
if ($type == "write_tournament") {
	$data = $_SERVER["HTTP_DATA"] ?? NULL;
	$data = json_decode($data,true);
	$result = write_tournament($data);
	echo $result;
}

// adds teams for the given tournament to DB (x Calls to OPL-API) (1 for groups / more for tournaments and leagues)
if ($type == "get_teams_for_tournament") {
	$result = [];
	$dbcn = create_dbcn();
	$id = $_SERVER["HTTP_ID"] ?? NULL;
	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$id])->fetch_assoc();
	if ($tournament["eventType"] == "tournament") {
		$leagues = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'league'", [$id])->fetch_all(MYSQLI_ASSOC);
		foreach ($leagues as $league) {
			$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'group'", [$league["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
			foreach ($groups as $group) {
				array_push($result, ...get_teams_for_tournament($group["OPL_ID"]));
				sleep(1);
			}
		}
	} elseif ($tournament["eventType"] == "league") {
		$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'group'", [$id])->fetch_all(MYSQLI_ASSOC);
		foreach ($groups as $group) {
			array_push($result, ...get_teams_for_tournament($group["OPL_ID"]));
			sleep(1);
		}
	} else {
		array_push($result, ...get_teams_for_tournament($id));
	}
	echo json_encode($result);
	$dbcn->close();
}

// adds the players for all teams in the given tournament to DB  (x API-Calls) (x = number of teams)
if ($type == "get_players_for_tournament") {
	$result = [];
	$dbcn = create_dbcn();
	$id = $_SERVER["HTTP_ID"] ?? NULL;
	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$id])->fetch_assoc();
	if ($tournament["eventType"] == "tournament") {
		$leagues = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'league'", [$id])->fetch_all(MYSQLI_ASSOC);
		foreach ($leagues as $league) {
			$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'group'", [$league["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
			foreach ($groups as $group) {
				array_push($result, ...get_players_for_tournament($group["OPL_ID"]));
				sleep(1);
			}
		}
	} elseif ($tournament["eventType"] == "league") {
		$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'group'", [$id])->fetch_all(MYSQLI_ASSOC);
		foreach ($groups as $group) {
			array_push($result, ...get_players_for_tournament($group["OPL_ID"]));
			sleep(1);
		}
	} else {
		array_push($result, ...get_players_for_tournament($id));
	}
	echo json_encode($result);
	$dbcn->close();
}

if ($type == "get_players_for_team") {
	$dbcn = create_dbcn();
	$team_id = $_SERVER["HTTP_TEAMID"] ?? NULL;
	$tournament_id = $_SERVER["HTTP_TOURNAMENTID"] ?? NULL;
	$result = get_players_for_team($team_id, $tournament_id);
	echo json_encode($result);
	$dbcn->close();
}

// (x API-Calls für x Spieler im Team)
if ($type == "get_summonerNames_for_team") {
	$result = [];
	$dbcn = create_dbcn();
	$team_id = $_SERVER["HTTP_TEAMID"] ?? NULL;
	$tournament_id = $_SERVER["HTTP_TOURNAMENTID"] ?? NULL;
	if ($tournament_id != NULL){
		$players = $dbcn->execute_query("SELECT * FROM players p JOIN players_in_teams_in_tournament pit on p.OPL_ID = pit.OPL_ID_player WHERE OPL_ID_team = ? AND (OPL_ID_tournament = ? OR OPL_ID_tournament IN (SELECT OPL_ID_parent FROM tournaments WHERE eventType='league' AND OPL_ID = ?) OR OPL_ID_tournament IN (SELECT OPL_ID_parent FROM tournaments WHERE eventType='league' AND OPL_ID IN (SELECT OPL_ID_parent FROM tournaments WHERE eventType='group' AND OPL_ID = ?)))", [$team_id, $tournament_id, $tournament_id, $tournament_id])->fetch_all(MYSQLI_ASSOC);
	} else {
		$players = $dbcn->execute_query("SELECT * FROM players p JOIN players_in_teams pit on p.OPL_ID = pit.OPL_ID_player WHERE OPL_ID_team = ?", [$team_id])->fetch_all(MYSQLI_ASSOC);
	}
	foreach ($players as $player) {
		$result[] = get_summonerNames_for_player($player["OPL_ID"]);
		sleep(1);
	}
	echo json_encode($result);
	$dbcn->close();
}

// (x API-Calls für x (Sub)-Tournaments)
if ($type == "get_matchups_for_tournament") {
	$result = [];
	$dbcn = create_dbcn();
	$id = $_SERVER["HTTP_ID"] ?? NULL;
	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$id])->fetch_assoc();
	if ($tournament["eventType"] == "tournament") {
		$leagues = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'league'", [$id])->fetch_all(MYSQLI_ASSOC);
		foreach ($leagues as $league) {
			$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'group'", [$league["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
			foreach ($groups as $group) {
				array_push($result, ...get_matchups_for_tournament($group["OPL_ID"]));
				sleep(1);
			}
		}
	} elseif ($tournament["eventType"] == "league") {
		$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'group'", [$id])->fetch_all(MYSQLI_ASSOC);
		foreach ($groups as $group) {
			array_push($result, ...get_matchups_for_tournament($group["OPL_ID"]));
			sleep(1);
		}
	} else {
		array_push($result, ...get_matchups_for_tournament($id));
	}
	echo json_encode($result);
	$dbcn->close();
}

// (1 API-Call)
if ($type == "get_results_for_matchup") {
	$dbcn = create_dbcn();
	$id = $_SERVER["HTTP_ID"] ?? NULL;
	$result = get_results_for_matchup($id);
	echo json_encode($result);
	$dbcn->close();
}

if ($type == "calculate_standings_from_matchups") {
	$result = [];
	$dbcn = create_dbcn();
	$id = $_SERVER["HTTP_ID"] ?? NULL;
	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$id])->fetch_assoc();
	if ($tournament["eventType"] == "tournament") {
		$leagues = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'league'", [$id])->fetch_all(MYSQLI_ASSOC);
		foreach ($leagues as $league) {
			$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'group'", [$league["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
			foreach ($groups as $group) {
				$result[] = ["group"=>$group, "updates"=>calculate_standings_from_matchups($group["OPL_ID"])];
			}
		}
	} elseif ($tournament["eventType"] == "league") {
		$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'group'", [$id])->fetch_all(MYSQLI_ASSOC);
		foreach ($groups as $group) {
			$result[] = ["group"=>$group, "updates"=>calculate_standings_from_matchups($group["OPL_ID"])];
		}
	} else {
		$result[] = ["group"=>$tournament, "updates"=>calculate_standings_from_matchups($id)];
	}
	echo json_encode($result);
	$dbcn->close();
}