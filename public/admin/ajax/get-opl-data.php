<?php
$root = dirname(__DIR__,3);
include_once $root."/src/old_functions/admin/get-opl-data.php";
include_once $root . "/config/data.php";

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

if ($type == "get_event_children") {
	$id = $_SERVER["HTTP_ID"];
	$result = get_related_events($id);
	echo json_encode($result);
}
if ($type == "get_event_parents") {
	$id = $_SERVER["HTTP_ID"];
	$result = get_related_events($id, "parents");
	echo json_encode($result);
}

// adds teams for the given tournament to DB (x Calls to OPL-API) (1 for groups / more for tournaments and leagues)
if ($type == "get_teams_for_tournament") {
	$result = [];
	$dbcn = create_dbcn();
	$id = $_SERVER["HTTP_ID"] ?? NULL;
	$deletemissing = $_SERVER["HTTP_DELETEMISSING"] ?? NULL;
	$deletemissing = ($deletemissing == "true");
	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$id])->fetch_assoc();
	if ($tournament["eventType"] == "tournament") {
		$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_top_parent = ? AND (eventType = 'group' OR (eventType = 'league' AND format = 'swiss') OR eventType='wildcard')",[$tournament["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
		foreach ($groups as $group) {
			array_push($result, ...get_teams_for_tournament($group["OPL_ID"], $deletemissing));
			sleep(1);
		}
		$playoffs = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'playoffs'", [$tournament["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
		foreach ($playoffs as $playoff) {
			array_push($result, ...get_teams_for_tournament($playoff["OPL_ID"], $deletemissing));
			sleep(1);
		}
	} elseif ($tournament["eventType"] == "league" && $tournament["format"] != "swiss") {
		$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'group'", [$id])->fetch_all(MYSQLI_ASSOC);
		foreach ($groups as $group) {
			array_push($result, ...get_teams_for_tournament($group["OPL_ID"], $deletemissing));
			sleep(1);
		}
	} else {
		array_push($result, ...get_teams_for_tournament($id, $deletemissing));
	}
	echo json_encode($result);
	$dbcn->close();
}

if ($type == "update_team") {
	$dbcn = create_dbcn();
	$team_id = $_SERVER["HTTP_TEAMID"] ?? NULL;
	$result = update_team($team_id);
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
		$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_top_parent = ? AND (eventType = 'group' OR (eventType = 'league' AND format = 'swiss'))",[$tournament["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
		foreach ($groups as $group) {
			array_push($result, ...get_players_for_tournament($group["OPL_ID"]));
			sleep(1);
		}
		$playoffs = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'playoffs'", [$tournament["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
		foreach ($playoffs as $playoff) {
			array_push($result, ...get_players_for_tournament($playoff["OPL_ID"]));
			sleep(1);
		}
	} elseif ($tournament["eventType"] == "league" && $tournament["format"] != 'swiss') {
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
		$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?",[$tournament_id])->fetch_assoc();
		$top_tournament_id = $tournament["OPL_ID_top_parent"] ?? $tournament_id;
		$players = $dbcn->execute_query("SELECT * FROM players p JOIN players_in_teams_in_tournament pit on p.OPL_ID = pit.OPL_ID_player WHERE OPL_ID_team = ? AND OPL_ID_tournament = ?", [$team_id, $top_tournament_id])->fetch_all(MYSQLI_ASSOC);
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
if ($type == "get_riotids_for_team") {
	$result = [];
	$dbcn = create_dbcn();
	$team_id = $_SERVER["HTTP_TEAMID"] ?? NULL;
	$tournament_id = $_SERVER["HTTP_TOURNAMENTID"] ?? NULL;
	if ($tournament_id != NULL){
		$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?",[$tournament_id])->fetch_assoc();
		$top_tournament_id = $tournament["OPL_ID_top_parent"] ?? $tournament_id;
		$players = $dbcn->execute_query("SELECT * FROM players p JOIN players_in_teams_in_tournament pit on p.OPL_ID = pit.OPL_ID_player WHERE OPL_ID_team = ? AND OPL_ID_tournament = ?", [$team_id, $top_tournament_id])->fetch_all(MYSQLI_ASSOC);
	} else {
		$players = $dbcn->execute_query("SELECT * FROM players p JOIN players_in_teams pit on p.OPL_ID = pit.OPL_ID_player WHERE OPL_ID_team = ?", [$team_id])->fetch_all(MYSQLI_ASSOC);
	}
	foreach ($players as $player) {
		$result[] = get_riotid_for_player($player["OPL_ID"]);
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
	$deletemissing = $_SERVER["HTTP_DELETEMISSING"] ?? NULL;
	$deletemissing = ($deletemissing == "true");
	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$id])->fetch_assoc();
	if ($tournament["eventType"] == "tournament") {
		$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_top_parent = ? AND (eventType = 'group' OR (eventType = 'league' AND format = 'swiss'))",[$tournament["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
		foreach ($groups as $group) {
			array_push($result, ...get_matchups_for_tournament($group["OPL_ID"], $deletemissing));
			sleep(1);
		}
		$playoffs = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'playoffs'", [$tournament["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
		foreach ($playoffs as $playoff) {
			array_push($result, ...get_matchups_for_tournament($playoff["OPL_ID"], $deletemissing));
			sleep(1);
		}
	} elseif ($tournament["eventType"] == "league" && $tournament["format"] != "swiss") {
		$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'group'", [$id])->fetch_all(MYSQLI_ASSOC);
		foreach ($groups as $group) {
			array_push($result, ...get_matchups_for_tournament($group["OPL_ID"], $deletemissing));
			sleep(1);
		}
	} else {
		array_push($result, ...get_matchups_for_tournament($id, $deletemissing));
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
		$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_top_parent = ? AND (eventType = 'group' OR (eventType = 'league' AND format = 'swiss'))",[$tournament["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
		foreach ($groups as $group) {
			$result[] = ["group"=>$group, "updates"=>calculate_standings_from_matchups($group["OPL_ID"])];
		}
	} elseif ($tournament["eventType"] == "league" && $tournament["format"] != "swiss") {
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