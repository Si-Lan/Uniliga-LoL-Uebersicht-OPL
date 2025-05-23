<?php
include_once __DIR__."/../setup/data.php";
include_once __DIR__."/../functions/helper.php";

$dbcn = create_dbcn();

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

if ($type == "playoffs") {
	$tournamentID = $_SERVER["HTTP_TOURNAMENTID"] ?? $_GET["tournamentid"] ?? NULL;
	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournamentID])->fetch_assoc();
	$playoffs = [];
	if ($tournament["eventType"] == "tournament") {
		$playoffs = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType = 'playoffs' AND OPL_ID_parent = ?", [$tournamentID])->fetch_all(MYSQLI_ASSOC);
	} else {
		echo json_encode($playoffs);
		exit();
	}
	if (isset($_SERVER["HTTP_IDONLY"]) || isset($_GET["idonly"])) {
		$playoffids = [];
		foreach ($playoffs as $playoff) {
			$playoffids[] = $playoff["OPL_ID"];
		}
		echo json_encode($playoffids);
	} else {
		echo json_encode($playoffs);
	}
}

if ($type == "teams") {
	$tournamentID = $_SERVER["HTTP_TOURNAMENTID"] ?? NULL;
	if ($tournamentID == null) {
		$teams = $dbcn->execute_query("SELECT * FROM teams WHERE OPL_ID > 0")->fetch_all(MYSQLI_ASSOC);
		echo json_encode($teams);
		exit;
	}
	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournamentID])->fetch_assoc();
	$groups = [];
	if ($tournament["eventType"] == "tournament") {
		$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_top_parent = ? AND (eventType = 'group' OR (eventType = 'league' AND format = 'swiss') OR eventType = 'wildcard')",[$tournamentID])->fetch_all(MYSQLI_ASSOC);
	} elseif ($tournament["eventType"] == "league" && $tournament["format"] != "swiss") {
		$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType = 'group' AND OPL_ID_parent = ?", [$tournamentID])->fetch_all(MYSQLI_ASSOC);
	} elseif ($tournament["eventType"] == "group" || ($tournament["eventType"] == "league" && $tournament["format"] == "swiss") || $tournament["eventType"] == "wildcard") {
		$groups[] = $tournament;
	}
	$teams = [];
	foreach ($groups as $group) {
		if (isset($_SERVER["HTTP_PLAYERCOUNT"]) && isset($_SERVER["HTTP_NOPUUID"])) {
			$teams_from_group = $dbcn->execute_query("SELECT t.*, tit.*, COUNT(pit.OPL_ID_player) AS player_count FROM teams t JOIN teams_in_tournaments tit ON t.OPL_ID = tit.OPL_ID_team JOIN players_in_teams_in_tournament pit on t.OPL_ID = pit.OPL_ID_team AND tit.OPL_ID_group = ? JOIN players p on pit.OPL_ID_player = p.OPL_ID WHERE (p.PUUID IS NULL OR p.summonerID IS NULL) GROUP BY t.OPL_ID", [$group["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
		} elseif (isset($_SERVER["HTTP_PLAYERCOUNT"])) {
			$teams_from_group = $dbcn->execute_query("SELECT t.*, tit.*, COUNT(pit.OPL_ID_player) AS player_count FROM teams t JOIN teams_in_tournaments tit ON t.OPL_ID = tit.OPL_ID_team JOIN players_in_teams_in_tournament pit on t.OPL_ID = pit.OPL_ID_team WHERE tit.OPL_ID_group = ? GROUP BY t.OPL_ID", [$group["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
		} else {
			$teams_from_group = $dbcn->execute_query("SELECT * FROM teams t JOIN teams_in_tournaments tit on t.OPL_ID = tit.OPL_ID_team WHERE OPL_ID_group = ?", [$group["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
		}
		array_push($teams, ...$teams_from_group);
	}
	echo json_encode($teams);
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

if ($type == "team-and-players") {
	$teamID = $_SERVER["HTTP_TEAMID"] ?? NULL;
	$tournamentID = $_SERVER["HTTP_TOURNAMENTID"] ?? NULL;
	$teams_season_rank = $_SERVER["HTTP_TEAMS_SEASON_RANK"] ?? NULL;
	if ($teams_season_rank != NULL && $tournamentID != NULL) {
		$teamDB = $dbcn->execute_query("
									SELECT teams.*, tsr.avg_rank_tier, tsr.avg_rank_div, tsr.avg_rank_num, tsr2.avg_rank_tier as avg_rank_tier_2, tsr2.avg_rank_div as avg_rank_div_2, tsr2.avg_rank_num as avg_rank_num_2
									FROM teams
									    LEFT JOIN teams_tournament_rank as tsr
									        ON teams.OPL_ID = tsr.OPL_ID_team
									               AND tsr.OPL_ID_tournament = ?
									               AND tsr.second_ranked_split = FALSE
										LEFT JOIN teams_tournament_rank as tsr2
									        ON teams.OPL_ID = tsr2.OPL_ID_team
									               AND tsr2.OPL_ID_tournament = ?
									               AND tsr2.second_ranked_split = TRUE
									WHERE OPL_ID = ?", [$tournamentID,$tournamentID,$teamID])->fetch_assoc();
		$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournamentID])->fetch_assoc();
		$t_teamname = $dbcn->execute_query("SELECT * FROM team_name_history WHERE OPL_ID_team = ? AND (update_time < ? OR ? IS NULL) ORDER BY update_time DESC", [$teamID,$tournament["dateEnd"],$tournament["dateEnd"]])->fetch_assoc();
		$teamDB["name"] = $t_teamname["name"];
		$teamDB["shortName"] = $t_teamname["shortName"];
		$ranked_split_1 = $tournament["ranked_season"]."-".$tournament["ranked_split"];
		$ranked_split_2 = get_second_ranked_split_for_tournament($dbcn,$tournamentID,string:true);
		$current_split = get_current_ranked_split($dbcn,$tournamentID);
		$ranked_splits = [1=>$ranked_split_1, 2=>$ranked_split_2, "current"=>$current_split];
	} else {
		$teamDB = $dbcn->execute_query("SELECT * FROM teams WHERE OPL_ID = ?", [$teamID])->fetch_assoc();
		$ranked_splits = [];
	}
	$playersDB = $dbcn->execute_query("SELECT * FROM players JOIN players_in_teams_in_tournament pit on players.OPL_ID = pit.OPL_ID_player WHERE pit.OPL_ID_team = ? AND pit.OPL_ID_tournament = ?", [$teamID, $tournamentID])->fetch_all(MYSQLI_ASSOC);
	echo json_encode(["team"=>$teamDB, "players"=>$playersDB, "ranked_splits"=>$ranked_splits]);
}

if ($type == "players-in-match") {
	$matchID = $_SERVER["HTTP_MATCHID"] ?? NULL;
	$match = $dbcn->execute_query("SELECT * FROM matchups WHERE OPL_ID = ?", [$matchID])->fetch_assoc();
	$teams = $dbcn->execute_query("SELECT OPL_ID_team1, OPL_ID_team2 FROM matchups WHERE OPL_ID = ?", [$matchID])->fetch_row();
	$players1 = $dbcn->execute_query("SELECT OPL_ID FROM players JOIN players_in_teams_in_tournament pit on players.OPL_ID = pit.OPL_ID_player WHERE OPL_ID_team = ? AND PUUID IS NOT NULL", [$teams[0]])->fetch_all(MYSQLI_ASSOC);
	$players2 = $dbcn->execute_query("SELECT OPL_ID FROM players JOIN players_in_teams_in_tournament pit on players.OPL_ID = pit.OPL_ID_player WHERE OPL_ID_team = ? AND PUUID IS NOT NULL", [$teams[1]])->fetch_all(MYSQLI_ASSOC);
	function cut_players(array $playerlist):array {
		$newlength = count($playerlist);
		if (count($playerlist) >= 5) {
			$newlength = ceil(count($playerlist) / 2)+1;
		}
		shuffle($playerlist);
		return array_slice($playerlist,0,$newlength);
	}
	if (isset($_SERVER["HTTP_CUT_PLAYERS"])) {
		$players1 = cut_players($players1);
		$players2 = cut_players($players2);
	}
	$players = array_merge($players1,$players2);
	$ids = [];
	foreach ($players as $player) {
		$ids[] = $player["OPL_ID"];
	}
	if (isset($_SERVER["HTTP_IDONLY"])) {
		echo json_encode($ids);
	} else {
		echo json_encode($players);
	}
}

if ($type == "matchup") {
	$matchID = $_SERVER["HTTP_MATCHID"] ?? NULL;
	$id_only = isset($_SERVER["HTTP_IDONLY"]);
	$tournamentID_only = isset($_SERVER["HTTP_RETURNTOURNAMENTID"]);
	$match = $dbcn->execute_query("SELECT * FROM matchups WHERE OPL_ID = ?", [$matchID])->fetch_assoc();
	if ($id_only) {
		echo $match["OPL_ID"];
	} elseif ($tournamentID_only) {
		echo $match["OPL_ID_tournament"];
	} else {
		echo json_encode($match);
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

if ($type == "games-from-players-in-match") {
	$matchID = $_SERVER["HTTP_MATCHID"] ?? NULL;
	$match = $dbcn->execute_query("SELECT * FROM matchups WHERE OPL_ID = ?",[$matchID])->fetch_assoc();
	if (isset($_SERVER["HTTP_WITHPUUIDONLY"])) {
		$players1 = $dbcn->execute_query("SELECT OPL_ID FROM players JOIN players_in_teams ON players.OPL_ID = players_in_teams.OPL_ID_player WHERE OPL_ID_team = ? AND PUUID IS NOT NULL", [$match["OPL_ID_team1"]])->fetch_all(MYSQLI_ASSOC);
		$players2 = $dbcn->execute_query("SELECT OPL_ID FROM players JOIN players_in_teams ON players.OPL_ID = players_in_teams.OPL_ID_player WHERE OPL_ID_team = ? AND PUUID IS NOT NULL", [$match["OPL_ID_team2"]])->fetch_all(MYSQLI_ASSOC);
	} else {
		$players1 = $dbcn->execute_query("SELECT OPL_ID FROM players JOIN players_in_teams ON players.OPL_ID = players_in_teams.OPL_ID_player WHERE OPL_ID_team = ?", [$match["OPL_ID_team1"]])->fetch_all(MYSQLI_ASSOC);
		$players2 = $dbcn->execute_query("SELECT OPL_ID FROM players JOIN players_in_teams ON players.OPL_ID = players_in_teams.OPL_ID_player WHERE OPL_ID_team = ?", [$match["OPL_ID_team2"]])->fetch_all(MYSQLI_ASSOC);
	}
	$players = array_merge($players1,$players2);
	$games = array();
	foreach ($players as $player) {
		$games_from_player = $dbcn->execute_query("SELECT matches_gotten FROM players WHERE OPL_ID = ?", [$player["OPL_ID"]])->fetch_column();
		$games_from_player = json_decode($games_from_player);
		foreach ($games_from_player as $game) {
			if (!in_array($game,$games)) {
				$games[] = $game;
			}
		}
	}
	echo json_encode($games);
}

if ($type == "all-games") {
	if (isset($_SERVER["HTTP_NO_DATA_ONLY"])) {
		$games = $dbcn->execute_query("SELECT RIOT_matchID FROM games WHERE played_at IS NULL")->fetch_all(MYSQLI_ASSOC);
	} else {
		$games = $dbcn->execute_query("SELECT RIOT_matchID FROM games")->fetch_all(MYSQLI_ASSOC);
	}
	echo json_encode($games);
}

if ($type == "games-in-tournaments-time") {
	$tournamentID = $_SERVER["HTTP_TOURNAMENTID"] ?? NULL;
	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournamentID])->fetch_assoc();
	$root_tournamentID = $tournament["OPL_ID_top_parent"] ?? $tournament["OPL_ID"];
	$root_tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$root_tournamentID])->fetch_assoc();
	if (isset($_SERVER["HTTP_UNASSIGNED_ONLY"])) {
		$games = $dbcn->execute_query("SELECT RIOT_matchID FROM games WHERE (played_at BETWEEN ? AND ?) AND RIOT_matchID NOT IN (SELECT RIOT_matchID FROM games_in_tournament WHERE OPL_ID_tournament = ?)", [$root_tournament["dateStart"], $root_tournament["dateEnd"], $root_tournamentID])->fetch_all(MYSQLI_ASSOC);
	} else {
		$games = $dbcn->execute_query("SELECT RIOT_matchID FROM games WHERE (played_at BETWEEN ? AND ?)", [$root_tournament["dateStart"], $root_tournament["dateEnd"]])->fetch_all(MYSQLI_ASSOC);
	}
	echo json_encode($games);
}

if ($type == "match-games-teams-by-matchid") {
	$matchID = $_SERVER["HTTP_MATCHID"] ?? NULL;
	$match = $dbcn->execute_query("SELECT * FROM matchups WHERE OPL_ID = ?",[$matchID])->fetch_assoc();
	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = (SELECT OPL_ID_top_parent FROM tournaments WHERE OPL_ID = ?)", [$match["OPL_ID_tournament"]])->fetch_assoc();

	$games = $dbcn->execute_query("SELECT games.RIOT_matchID, gtm.OPL_ID_matches AS OPL_ID_match, gtm.OPL_ID_blueTeam, gtm.OPL_ID_redTeam FROM games JOIN games_to_matches gtm on games.RIOT_matchID = gtm.RIOT_matchID WHERE OPL_ID_matches = ? ORDER BY RIOT_matchID",[$matchID])->fetch_all(MYSQLI_ASSOC);
	$team1 = $dbcn->execute_query("SELECT * FROM teams LEFT JOIN team_name_history tnh ON tnh.OPL_ID_team = teams.OPL_ID AND (update_time < ? OR ? IS NULL) WHERE OPL_ID = ? ORDER BY update_time DESC",[$tournament["dateEnd"],$tournament["dateEnd"],$match["OPL_ID_team1"]])->fetch_assoc();
	$team2 = $dbcn->execute_query("SELECT * FROM teams LEFT JOIN team_name_history tnh ON tnh.OPL_ID_team = teams.OPL_ID AND (update_time < ? OR ? IS NULL) WHERE OPL_ID = ? ORDER BY update_time DESC",[$tournament["dateEnd"],$tournament["dateEnd"],$match["OPL_ID_team2"]])->fetch_assoc();
	$result = json_encode(array("match"=>$match, "games"=>$games, "team1"=>$team1, "team2"=>$team2, "tournament"=>$tournament));
	echo $result;
}

if ($type == "tournaments") {
	if (isset($_SERVER["HTTP_ACTIVE"]) || isset($_GET["active"])) {
		$tournaments = $dbcn->query("SELECT * FROM tournaments WHERE eventType = 'tournament' AND finished = false AND archived = false")->fetch_all(MYSQLI_ASSOC);
	} else {
		$tournaments = $dbcn->query("SELECT * FROM tournaments WHERE eventType = 'tournament'")->fetch_all(MYSQLI_ASSOC);
	}
	if (isset($_SERVER["HTTP_IDONLY"]) || isset($_GET["idonly"])) {
		$tids = [];
		foreach ($tournaments as $tournament) {
			$tids[] = $tournament['OPL_ID'];
		}
		echo json_encode($tids);
	} else {
		echo json_encode($tournaments);
	}
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