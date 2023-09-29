<?php
include_once __DIR__."/../setup/data.php";

$dbcn = create_dbcn();

$type = $_SERVER["HTTP_TYPE"] ?? $_REQUEST["type"] ?? NULL;
if ($type == NULL) exit;

if ($type == "groups") {
	$tournamentID = $_SERVER["HTTP_TOURNAMENTID"] ?? NULL;
	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournamentID])->fetch_assoc();
	$groups = [];
	if ($tournament["eventType"] == "tournament") {
		$leagues = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType = 'league' AND OPL_ID_parent = ?", [$tournamentID])->fetch_all(MYSQLI_ASSOC);
		foreach ($leagues as $league) {
			$groups_from_league = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType = 'group' AND OPL_ID_parent = ?", [$league["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
			array_push($groups, ...$groups_from_league);
		}
	} elseif ($tournament["eventType"] == "league") {
		$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType = 'group' AND OPL_ID_parent = ?", [$tournamentID])->fetch_all(MYSQLI_ASSOC);
	}
	echo json_encode($groups);
}

if ($type == "teams") {
	$tournamentID = $_SERVER["HTTP_TOURNAMENTID"] ?? NULL;
	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournamentID])->fetch_assoc();
	$groups = [];
	if ($tournament["eventType"] == "tournament") {
		$leagues = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType = 'league' AND OPL_ID_parent = ?", [$tournamentID])->fetch_all(MYSQLI_ASSOC);
		foreach ($leagues as $league) {
			$groups_from_league = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType = 'group' AND OPL_ID_parent = ?", [$league["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
			array_push($groups, ...$groups_from_league);
		}
	} elseif ($tournament["eventType"] == "league") {
		$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType = 'group' AND OPL_ID_parent = ?", [$tournamentID])->fetch_all(MYSQLI_ASSOC);
	} elseif ($tournament["eventType"] == "group") {
		$groups[] = $tournament;
	}
	$teams = [];
	foreach ($groups as $group) {
		$teams_from_group = $dbcn->execute_query("SELECT * FROM teams t JOIN teams_in_tournaments tit on t.OPL_ID = tit.OPL_ID_team WHERE OPL_ID_tournament = ?", [$group["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
		array_push($teams, ...$teams_from_group);
	}
	echo json_encode($teams);
}

if ($type == "team-and-players") {
	$teamID = $_SERVER["HTTP_TEAMID"] ?? NULL;
	$teamDB = $dbcn->execute_query("SELECT * FROM teams WHERE OPL_ID = ?", [$teamID])->fetch_assoc();
	$playersDB = $dbcn->execute_query("SELECT * FROM players JOIN players_in_teams pit on players.OPL_ID = pit.OPL_ID_player WHERE pit.OPL_ID_team = ?", [$teamID])->fetch_all(MYSQLI_ASSOC);
	echo json_encode(["team"=>$teamDB, "players"=>$playersDB]);
}

if ($type == "matchups") {
	$tournamentID = $_SERVER["HTTP_TOURNAMENTID"] ?? NULL;
	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournamentID])->fetch_assoc();
	$groups = [];
	if ($tournament["eventType"] == "tournament") {
		$leagues = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType = 'league' AND OPL_ID_parent = ?", [$tournamentID])->fetch_all(MYSQLI_ASSOC);
		foreach ($leagues as $league) {
			$groups_from_league = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType = 'group' AND OPL_ID_parent = ?", [$league["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
			array_push($groups, ...$groups_from_league);
		}
	} elseif ($tournament["eventType"] == "league") {
		$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType = 'group' AND OPL_ID_parent = ?", [$tournamentID])->fetch_all(MYSQLI_ASSOC);
	} elseif ($tournament["eventType"] == "group") {
		$groups[] = $tournament;
	}
	$matchups = [];
	foreach ($groups as $group) {
		$matchup_from_group = $dbcn->execute_query("SELECT * FROM matchups WHERE OPL_ID_tournament = ?", [$group["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
		array_push($matchups, ...$matchup_from_group);
	}
	echo json_encode($matchups);
}

$dbcn->close();