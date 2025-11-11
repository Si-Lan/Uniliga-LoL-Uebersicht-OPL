<?php
include_once dirname(__DIR__,2)."/bootstrap.php";
include_once dirname(__DIR__,2)."/src/old_functions/helper.php";

$dbcn = \App\Core\DatabaseConnection::getConnection();

$type = $_SERVER["HTTP_TYPE"] ?? $_REQUEST["type"] ?? NULL;
if ($type == NULL) exit;

if ($type == "groups") {
	$tournamentID = $_SERVER["HTTP_TOURNAMENTID"] ?? $_GET["tournamentid"] ?? NULL;
	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournamentID])->fetch_assoc();
	$groups = [];
	if ($tournament["eventType"] == "tournament") {
		$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_top_parent = ? AND (eventType = 'group' OR (eventType = 'league' AND format = 'swiss'))",[$tournamentID])->fetch_all(MYSQLI_ASSOC);
	} elseif ($tournament["eventType"] == "league" && $tournament["format"] != "swiss") {
		$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType = 'group' AND OPL_ID_parent = ?", [$tournamentID])->fetch_all(MYSQLI_ASSOC);
	}
	if (isset($_SERVER["HTTP_IDONLY"]) || isset($_GET["idonly"])) {
		$groupIDs = [];
		foreach ($groups as $group) {
			$groupIDs[] = $group['OPL_ID'];
		}
		echo json_encode($groupIDs);
	} else {
		echo json_encode($groups);
	}
}

if ($type == "players") {
	$players = [];
	$tournamentID = $_SERVER["HTTP_TOURNAMENTID"] ?? $_GET["tournamentid"] ?? NULL;
	$teamID = $_SERVER["HTTP_TEAMID"] ?? NULL;

	if ($tournamentID != NULL) {
		$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournamentID])->fetch_assoc();
		$groups = [];
		$parent_tournament = NULL;
		if ($tournament["eventType"] == "tournament") {
			$parent_tournament = $tournamentID;
			$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_top_parent = ? AND (eventType = 'group' OR (eventType = 'league' AND format = 'swiss') OR eventType = 'wildcard')",[$tournamentID])->fetch_all(MYSQLI_ASSOC);
		} elseif ($tournament["eventType"] == "league" && $tournament["format"] != "swiss") {
			$parent_tournament = $tournament["OPL_ID_parent"];
			$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType = 'group' AND OPL_ID_parent = ?", [$tournamentID])->fetch_all(MYSQLI_ASSOC);
		} elseif ($tournament["eventType"] == "group" || ($tournament["eventType"] == "league" && $tournament["format"] == "swiss") || $tournament["eventType"] == "wildcard") {
			$parent_tournament = $dbcn->execute_query("SELECT OPL_ID_parent FROM tournaments WHERE eventType='league' AND OPL_ID = ?", [$tournament["OPL_ID_parent"]])->fetch_column();
			$groups[] = $tournament;
		}

		if ($teamID != NULL) {
			// Players from Team in Tournament
			if (isset($_SERVER["HTTP_SUMMONERIDSET"])) {
				$players = $dbcn->execute_query("SELECT * FROM players JOIN players_in_teams_in_tournament pit ON players.OPL_ID = pit.OPL_ID_player WHERE pit.OPL_ID_team = ? AND pit.OPL_ID_tournament = ? AND summonerID IS NOT NULL", [$teamID, $parent_tournament])->fetch_all(MYSQLI_ASSOC);
			} elseif (isset($_SERVER["HTTP_PUUIDSET"])) {
				$players = $dbcn->execute_query("SELECT * FROM players JOIN players_in_teams_in_tournament pit ON players.OPL_ID = pit.OPL_ID_player WHERE pit.OPL_ID_team = ? AND pit.OPL_ID_tournament = ? AND PUUID IS NOT NULL", [$teamID, $parent_tournament])->fetch_all(MYSQLI_ASSOC);
			} else {
				$players = $dbcn->execute_query("SELECT * FROM players JOIN players_in_teams_in_tournament pit ON players.OPL_ID = pit.OPL_ID_player WHERE pit.OPL_ID_team = ? AND pit.OPL_ID_tournament = ?", [$teamID, $parent_tournament])->fetch_all(MYSQLI_ASSOC);
			}
		} else {
			// Players from Tournament
			foreach ($groups as $group) {
				if (isset($_SERVER["HTTP_SUMMONERIDSET"])) {
					$players_from_group = $dbcn->execute_query("SELECT p.* FROM players AS p JOIN players_in_teams pit on p.OPL_ID = pit.OPL_ID_player JOIN teams_in_tournaments tit on pit.OPL_ID_team = tit.OPL_ID_team WHERE tit.OPL_ID_group = ? AND summonerID IS NOT NULL", [$group["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
				} elseif (isset($_SERVER["HTTP_PUUIDSET"])) {
					$players_from_group = $dbcn->execute_query("SELECT p.* FROM players AS p JOIN players_in_teams pit on p.OPL_ID = pit.OPL_ID_player JOIN teams_in_tournaments tit on pit.OPL_ID_team = tit.OPL_ID_team WHERE tit.OPL_ID_group = ? AND PUUID IS NOT NULL", [$group["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
				} else {
					$players_from_group = $dbcn->execute_query("SELECT p.* FROM players AS p JOIN players_in_teams pit on p.OPL_ID = pit.OPL_ID_player JOIN teams_in_tournaments tit on pit.OPL_ID_team = tit.OPL_ID_team WHERE tit.OPL_ID_group = ?", [$group["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
				}
				array_push($players, ...$players_from_group);
			}
		}
	} else {
		// Players from Team
		if ($teamID != null) {
			if (isset($_SERVER["HTTP_SUMMONERIDSET"])) {
				$players = $dbcn->execute_query("SELECT * FROM players JOIN players_in_teams pit ON players.OPL_ID = pit.OPL_ID_player WHERE pit.OPL_ID_team = ? AND summonerID IS NOT NULL", [$teamID])->fetch_all(MYSQLI_ASSOC);
			} elseif (isset($_SERVER["HTTP_PUUIDSET"])) {
				$players = $dbcn->execute_query("SELECT * FROM players JOIN players_in_teams pit ON players.OPL_ID = pit.OPL_ID_player WHERE pit.OPL_ID_team = ? AND PUUID IS NOT NULL", [$teamID])->fetch_all(MYSQLI_ASSOC);
			} else {
				$players = $dbcn->execute_query("SELECT * FROM players JOIN players_in_teams pit ON players.OPL_ID = pit.OPL_ID_player WHERE pit.OPL_ID_team = ?", [$teamID])->fetch_all(MYSQLI_ASSOC);
			}
		} else {
			// All players
			if (isset($_SERVER["HTTP_SUMMONERIDSET"])) {
				$players = $dbcn->execute_query("SELECT * FROM players WHERE summonerID IS NOT NULL")->fetch_all(MYSQLI_ASSOC);
			} elseif (isset($_SERVER["HTTP_PUUIDSET"])) {
				$players = $dbcn->execute_query("SELECT * FROM players WHERE PUUID IS NOT NULL")->fetch_all(MYSQLI_ASSOC);
			} else {
				$players = $dbcn->execute_query("SELECT * FROM players")->fetch_all(MYSQLI_ASSOC);
			}
		}
	}

	if (isset($_SERVER["HTTP_SUMMONERSONLY"])) {
		$summoners = [];
		foreach ($players as $player) {
			$summoners[] = $player["summonerName"];
		}
		$result = $summoners;
	} else {
		$result = $players;
	}

	$uniq_result = array_values(array_unique($result, SORT_REGULAR));

	if (isset($_SERVER["HTTP_AMOUNT"]) || isset($_GET["amount"])) {
		echo count($uniq_result);
	} else {
		echo json_encode($result);
	}
}

if ($type == "matchups") {
	$tournamentID = $_SERVER["HTTP_TOURNAMENTID"] ?? NULL;
	$teamID = $_SERVER["HTTP_TEAMID"] ?? NULL;
	$id_only = isset($_SERVER["HTTP_IDONLY"]);
	$unplayed_only = isset($_SERVER["HTTP_UNPLAYEDONLY"]);
	$unplayed_addon = $unplayed_only ? "AND played IS FALSE" : "";
	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournamentID])->fetch_assoc();
	$groups = [];
	if ($tournament["eventType"] == "tournament") {
		$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_top_parent = ? AND (eventType = 'group' OR (eventType = 'league' AND format = 'swiss') OR eventType='wildcard')",[$tournamentID])->fetch_all(MYSQLI_ASSOC);
		$playoffs = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType = 'playoffs' AND OPL_ID_parent = ?", [$tournament["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
		array_push($groups, ...$playoffs);
	} elseif ($tournament["eventType"] == "league" && $tournament["format"] != "swiss") {
		$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType = 'group' AND OPL_ID_parent = ?", [$tournamentID])->fetch_all(MYSQLI_ASSOC);
	} elseif ($tournament["eventType"] == "group" || $tournament["eventType"] == "playoffs" || ($tournament["eventType"] == "league" && $tournament["format"] == "swiss") || $tournament["eventType"] == "wildcard") {
		$groups[] = $tournament;
	}
	$matchups = [];
	foreach ($groups as $group) {
		if ($teamID == NULL) {
			$matchup_from_group = $dbcn->execute_query("SELECT * FROM matchups WHERE OPL_ID_tournament = ? {$unplayed_addon}", [$group["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
		} else {
			$matchup_from_group = $dbcn->execute_query("SELECT * FROM matchups WHERE OPL_ID_tournament = ? AND (OPL_ID_team1 = ? OR OPL_ID_team2 = ?) {$unplayed_addon}", [$group["OPL_ID"],$teamID,$teamID])->fetch_all(MYSQLI_ASSOC);
		}
		if ($id_only) {
			foreach ($matchup_from_group as $matchup) {
				$matchups[] = $matchup["OPL_ID"];
			}
		} else {
			array_push($matchups, ...$matchup_from_group);
		}
	}
	echo json_encode($matchups);
}

if ($type == "last-update-time") {
	$update_type = $_SERVER['HTTP_UPDATETYPE'] ?? NULL;
	$item_ID = $_SERVER['HTTP_ITEMID'] ?? NULL;
	$tournamentID = $_SERVER["HTTP_TOURNAMENTID"] ?? $_REQUEST["t"] ?? NULL;
	if ($item_ID == NULL || $update_type == NULL) exit;
	if ($update_type == "group") {
		$last_update = $dbcn->execute_query("SELECT last_update FROM updates_user_group WHERE OPL_ID_group = ?", [$item_ID])->fetch_column();
	} elseif ($update_type == "team") {
		$last_update = $dbcn->execute_query("SELECT last_update FROM updates_user_team WHERE OPL_ID_team = ?", [$item_ID])->fetch_column();
	} elseif ($update_type == "match") {
		$last_update = $dbcn->execute_query("SELECT last_update FROM updates_user_matchup WHERE OPL_ID_matchup = ?", [$item_ID])->fetch_column();
	} else {
		exit;
	}
	if ($tournamentID != NULL) {
		$last_cron_update = $dbcn->execute_query("SELECT last_update FROM updates_cron WHERE OPL_ID_tournament = ?", [$tournamentID])->fetch_column();
	} elseif ($update_type == "team") {
		$last_cron_update = $dbcn->execute_query("SELECT last_update FROM updates_cron JOIN teams_in_tournaments tit on updates_cron.OPL_ID_tournament = tit.OPL_ID_group WHERE OPL_ID_team = ? ORDER BY last_update DESC", [$item_ID])->fetch_column();
	} elseif ($update_type == "match") {
		$match_tournament_id = $dbcn->execute_query("SELECT OPL_ID_tournament FROM matchups WHERE OPL_ID = ?", [$item_ID])->fetch_column();
		$parent_tournament_id = get_top_parent_tournament($dbcn, $match_tournament_id);
		$last_cron_update = $dbcn->execute_query("SELECT last_update FROM updates_cron WHERE OPL_ID_tournament = ? ORDER BY last_update DESC", [$parent_tournament_id])->fetch_column();
	} elseif ($update_type == "group") {
		$last_cron_update = $dbcn->execute_query("SELECT last_update FROM updates_cron JOIN tournaments l ON eventType='league' AND l.OPL_ID_parent = updates_cron.OPL_ID_tournament JOIN tournaments g ON g.eventType = 'group' AND g.OPL_ID_parent = l.OPL_ID WHERE g.OPL_ID = ? ORDER BY last_update DESC", [$item_ID])->fetch_column();
	} else {
		$last_cron_update = 0;
	}
	$last_update = max($last_update,$last_cron_update);
	$return_relative_time_string = $_SERVER["HTTP_RELATIVETIME"] ?? FALSE;
	if ($return_relative_time_string) {
		if ($last_update == NULL) {
			echo "unbekannt";
		} else {
			echo max_time_from_timestamp(time() - strtotime($last_update));
		}
	} else {
		echo $last_update;
	}
}

$dbcn->close();