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
		if (isset($_SERVER["HTTP_PLAYERCOUNT"]) && isset($_SERVER["HTTP_NOPUUID"])) {
			$teams_from_group = $dbcn->execute_query("SELECT t.*, tit.*, COUNT(pit.OPL_ID_player) AS player_count FROM teams t JOIN teams_in_tournaments tit ON t.OPL_ID = tit.OPL_ID_team JOIN players_in_teams pit on t.OPL_ID = pit.OPL_ID_team JOIN players p on pit.OPL_ID_player = p.OPL_ID WHERE (p.PUUID IS NULL OR p.summonerID IS NULL) AND tit.OPL_ID_tournament = ? GROUP BY t.OPL_ID", [$group["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
		} elseif (isset($_SERVER["HTTP_PLAYERCOUNT"])) {
			$teams_from_group = $dbcn->execute_query("SELECT t.*, tit.*, COUNT(pit.OPL_ID_player) AS player_count FROM teams t JOIN teams_in_tournaments tit ON t.OPL_ID = tit.OPL_ID_team JOIN players_in_teams pit on t.OPL_ID = pit.OPL_ID_team WHERE tit.OPL_ID_tournament = ? GROUP BY t.OPL_ID", [$group["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
		} else {
			$teams_from_group = $dbcn->execute_query("SELECT * FROM teams t JOIN teams_in_tournaments tit on t.OPL_ID = tit.OPL_ID_team WHERE OPL_ID_tournament = ?", [$group["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
		}
		array_push($teams, ...$teams_from_group);
	}
	echo json_encode($teams);
}

if ($type == "players") {
	$players = [];
	if (isset($_SERVER["HTTP_TEAMID"])) {
		$teamID = $_SERVER["HTTP_TEAMID"];
		if (isset($_SERVER["HTTP_SUMMONERIDSET"])) {
			$players = $dbcn->execute_query("SELECT * FROM players JOIN players_in_teams pit on players.OPL_ID = pit.OPL_ID_player WHERE pit.OPL_ID_team = ? AND summonerID IS NOT NULL", [$teamID])->fetch_all(MYSQLI_ASSOC);
		} elseif (isset($_SERVER["HTTP_PUUIDSET"])) {
			$players = $dbcn->execute_query("SELECT * FROM players JOIN players_in_teams pit on players.OPL_ID = pit.OPL_ID_player WHERE pit.OPL_ID_team = ? AND PUUID IS NOT NULL", [$teamID])->fetch_all(MYSQLI_ASSOC);
		} else {
			$players = $dbcn->execute_query("SELECT * FROM players JOIN players_in_teams pit on players.OPL_ID = pit.OPL_ID_player WHERE pit.OPL_ID_team = ?", [$teamID])->fetch_all(MYSQLI_ASSOC);
		}
	} elseif (isset($_SERVER["HTTP_TOURNAMENTID"])) {
		$tournamentID = $_SERVER["HTTP_TOURNAMENTID"];
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
		$players = [];
		foreach ($groups as $group) {
			if (isset($_SERVER["HTTP_SUMMONERIDSET"])) {
				$players_from_group = $dbcn->execute_query("SELECT p.* FROM players AS p JOIN players_in_teams pit on p.OPL_ID = pit.OPL_ID_player JOIN teams_in_tournaments tit on pit.OPL_ID_team = tit.OPL_ID_team WHERE tit.OPL_ID_tournament = ? AND summonerID IS NOT NULL", [$group["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
			} elseif (isset($_SERVER["HTTP_PUUIDSET"])) {
				$players_from_group = $dbcn->execute_query("SELECT p.* FROM players AS p JOIN players_in_teams pit on p.OPL_ID = pit.OPL_ID_player JOIN teams_in_tournaments tit on pit.OPL_ID_team = tit.OPL_ID_team WHERE tit.OPL_ID_tournament = ? AND PUUID IS NOT NULL", [$group["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
			} else {
				$players_from_group = $dbcn->execute_query("SELECT p.* FROM players AS p JOIN players_in_teams pit on p.OPL_ID = pit.OPL_ID_player JOIN teams_in_tournaments tit on pit.OPL_ID_team = tit.OPL_ID_team WHERE tit.OPL_ID_tournament = ?", [$group["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
			}
			array_push($players, ...$players_from_group);
		}
	}

	echo json_encode($players);
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


if ($type == "local_patch_info") {
	$patch = $_SERVER["HTTP_PATCH"] ?? NULL;
	if ($patch == "all") {
		$patch_data = $dbcn->execute_query("SELECT * FROM local_patches")->fetch_all(MYSQLI_ASSOC);
	} else {
		$patch_data = $dbcn->execute_query("SELECT * FROM local_patches WHERE patch = ?", [$patch])->fetch_all(MYSQLI_ASSOC);
	}
	echo json_encode($patch_data);
}

$dbcn->close();