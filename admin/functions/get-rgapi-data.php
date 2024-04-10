<?php
include_once __DIR__."/../../setup/data.php";
include_once __DIR__."/../../functions/helper.php";

// sendet X Anfragen an Riot API (Summoner-V4)  (X = Anzahl Spieler im Team)
function get_puuids_by_team($teamID, $all = FALSE):array {
	$returnArr = array("return"=>0, "echo"=>"", "writesP"=>0, "writesS"=>0, "changes"=>[0,[]], "RGAPI-Calls"=>0,"404"=>0);
	$dbcn = create_dbcn();
	$RGAPI_Key = get_rgapi_key();

	if ($dbcn -> connect_error){
		$returnArr["return"] = 1;
		$returnArr["echo"] .= "<span style='color: red'>Database Connection failed : " . $dbcn->connect_error . "<br><br></span>";
		return $returnArr;
	}
	$teamDB = $dbcn->query("SELECT * FROM teams WHERE OPL_ID = {$teamID}")->fetch_assoc();
	$returnArr["echo"] .= "<span style='color: royalblue'>writing PUUIDS for Players from {$teamDB['name']} :<br></span>";

	if ($all){
		$playersDB = $dbcn->query("SELECT * FROM players JOIN players_in_teams pit on players.OPL_ID = pit.OPL_ID_player WHERE OPL_ID_team = {$teamID}")->fetch_all(MYSQLI_ASSOC);
	} else {
		$playersDB = $dbcn->query("SELECT * FROM players JOIN players_in_teams pit on players.OPL_ID = pit.OPL_ID_player WHERE OPL_ID_team = {$teamID} AND (PUUID IS NULL OR SummonerID IS NULL)")->fetch_all(MYSQLI_ASSOC);
	}

	foreach ($playersDB as $player) {
		if ($player["riotID_name"] == null) continue;
		$name_safe = urlencode($player['riotID_name']);
		$tag_safe = urlencode($player['riotID_tag']);
		$returnArr['echo'] .= "<span style='color: lightskyblue'>-writing PUUID for {$player['riotID_name']}#{$player['riotID_tag']} :<br></span>";

		$options = ["http" => ["header" => "X-Riot-Token: $RGAPI_Key"]];
		$context = stream_context_create($options);

		$content_riot = @file_get_contents("https://europe.api.riotgames.com/riot/account/v1/accounts/by-riot-id/$name_safe/$tag_safe", false, $context);

		$returnArr["RGAPI-Calls"] += 1;
		if ($content_riot === FALSE) {
			$returnArr["echo"] .= "<span style='color: orangered'>--could not get PUUID, request failed: {$http_response_header[0]}<br></span>";
			if (str_contains($http_response_header[0], "404")) {
				$returnArr["404"]++;
			}
			continue;
		}
		if (str_contains($http_response_header[0], "200")) {
			$data = json_decode($content_riot, true);
			$returnArr["echo"] .= "<span style='color: limegreen'>--got PUUID: {$data['puuid']}<br></span>";

			$playerinDB = $dbcn->query("SELECT * FROM players WHERE OPL_ID = {$player['OPL_ID']}")->fetch_assoc();
			if ($playerinDB['PUUID'] == NULL) {
				$returnArr["echo"] .= "<span style='color: lawngreen'>---write PUUID to DB<br></span>";
				$returnArr["writesP"]++;
				$dbcn->query("UPDATE players SET PUUID = '{$data['puuid']}' WHERE OPL_ID = {$player['OPL_ID']}");
			} else {
				$returnArr["echo"] .= "<span style='color: orange'>---Player already has a PUUID in DB<br></span>";
				if ($playerinDB['PUUID'] == $data['puuid']) {
					$returnArr["echo"] .= "<span style='color: yellow'>----PUUID unchanged<br></span>";
				} else {
					$returnArr["echo"] .= "<span style='color: lawngreen'>----PUUID changed, update DB<br></span>";
					$dbcn->query("UPDATE players SET PUUID = '{$data['puuid']}' WHERE OPL_ID = {$player['OPL_ID']}");
				}
			}

			$content_summoner = @file_get_contents("https://euw1.api.riotgames.com/lol/summoner/v4/summoners/by-puuid/{$data['puuid']}", false, $context);

			if ($content_summoner === FALSE) {
				$returnArr["echo"] .= "<span style='color: orangered'>--could not get SummonerID, request failed: {$http_response_header[0]}<br></span>";
				if (str_contains($http_response_header[0], "404")) {
					$returnArr["404"]++;
				}
				continue;
			}
			if (str_contains($http_response_header[0], "200")) {
				$data_summoner = json_decode($content_summoner, true);
				if ($playerinDB['summonerID'] == NULL) {
					$returnArr["echo"] .= "<span style='color: lawngreen'>---write SummonerID to DB<br></span>";
					$returnArr["writesS"]++;
					$dbcn->query("UPDATE players SET summonerID = '{$data_summoner['id']}' WHERE OPL_ID = {$player['OPL_ID']}");
				} else {
					$returnArr["echo"] .= "<span style='color: orange'>---Player already has a SummonerID in DB<br></span>";
					if ($playerinDB['summonerID'] == $data_summoner['id']) {
						$returnArr["echo"] .= "<span style='color: yellow'>----SummonerID unchanged<br></span>";
					} else {
						$returnArr["echo"] .= "<span style='color: lawngreen'>----SummonerID changed, update DB<br></span>";
						$dbcn->query("UPDATE players SET summonerID = '{$data_summoner['id']}' WHERE OPL_ID = {$player['OPL_ID']}");
					}
				}
			} else {
				$response = explode(" ", $http_response_header[0])[1];
				$returnArr["echo"] .= "<span style='color: orangered'>--could not get SummonerID, response-code: $response<br></span>";
			}
		} else {
			$response = explode(" ", $http_response_header[0])[1];
			$returnArr["echo"] .= "<span style='color: orangered'>--could not get PUUID, response-code: $response<br></span>";
		}
	}

	return $returnArr;
}

function get_riotid_for_player_by_puuid($playerID) {
	$returnArr = array("return"=>0, "echo"=>"", "writes"=>0, "updates"=>0);
	$dbcn = create_dbcn();
	$RGAPI_Key = get_rgapi_key();
	if ($dbcn -> connect_error){
		$returnArr["return"] = 1;
		$returnArr["echo"] .= "<span style='color: red'>Database Connection failed : " . $dbcn->connect_error . "<br><br></span>";
		return $returnArr;
	}

	$player = $dbcn->execute_query("SELECT * FROM players WHERE OPL_ID = ?", [$playerID])->fetch_assoc();
	$returnArr["echo"] .= "<span style='color: royalblue'>writing RiotID for {$player['name']} :<br></span>";

	if ($player["PUUID"] == NULL) {
		$returnArr["echo"] .= "<span style='color: orangered'>--could not get RiotID, PUUID is missing<br></span>";
		return $returnArr;
	}

	$options = ["http" => ["header" => "X-Riot-Token: $RGAPI_Key"]];
	$context = stream_context_create($options);
	$content = @file_get_contents("https://europe.api.riotgames.com/riot/account/v1/accounts/by-puuid/{$player['PUUID']}", false,$context);

	if ($content === FALSE || !str_contains($http_response_header[0], "200")) {
		$returnArr["echo"] .= "<span style='color: orangered'>--could not get RiotID, request failed: {$http_response_header[0]}<br></span>";
		return $returnArr;
	}

	$data = json_decode($content, true);
	$gameName = $data["gameName"];
	$tagLine = $data["tagLine"];

	$returnArr["echo"] .= "<span style='color: limegreen'>--got RiotID: $gameName#$tagLine<br></span>";

	if ($player["riotID_name"] == $gameName && $player["riotID_tag"] == $tagLine) {
		$returnArr["echo"] .= "<span style='color: orangered'>----RiotID unchanged<br></span>";
		return $returnArr;
	}

	$dbcn->execute_query("UPDATE players SET riotID_name = ?, riotID_tag = ? WHERE OPL_ID = ?", [$gameName, $tagLine, $playerID]);

	if ($player["riotID_name"] == null || $player["riotID_tag"] == null) {
		$returnArr["echo"] .= "<span style='color: limegreen'>----RiotID written<br></span>";
		$returnArr["writes"] = 1;
	} else {
		$returnArr["echo"] .= "<span style='color: limegreen'>----RiotID updated<br>------{$player["riotID_name"]}#{$player["riotID_tag"]} zu $gameName#$tagLine<br></span>";
		$returnArr["updates"] = 1;
	}

	return $returnArr;
}

// sendet 1 Anfrage an Riot API (Match-V5)
// tournamentID benötigt um Zeitrahmen einzugrenzen
function get_games_by_player($playerID, $tournamentID) {
	$returnArr = array("return"=>0, "echo"=>"", "writes"=>0, "already"=>0);
	$dbcn = create_dbcn();
	$RGAPI_Key = get_rgapi_key();
	if ($dbcn -> connect_error){
		$returnArr["return"] = 1;
		$returnArr["echo"] .= "<span style='color: red'>Database Connection failed : " . $dbcn->connect_error . "<br><br></span>";
		return $returnArr;
	}

	$player = $dbcn->query("SELECT p.* FROM players p JOIN players_in_teams pit ON p.OPL_ID = pit.OPL_ID_player WHERE OPL_ID = {$playerID}")->fetch_assoc();
	$tournament = $dbcn->query("SELECT * FROM tournaments WHERE OPL_ID = {$tournamentID}")->fetch_assoc();
	$returnArr["echo"] .= "<span style='color: royalblue'>writing Matches for {$player['name']} :<br></span>";
	$matches_from_player = json_decode($player["matches_gotten"]);

	$tournament_start = strtotime($tournament['dateStart'])-(86400*7); // eine woche puffer
	$tournament_end = strtotime($tournament['dateEnd'])+86400; // ein Tag Puffer

	if ($player["PUUID"] == NULL) {
		$returnArr["echo"] .= "<span style='color: orangered'>--could not get Games, PUUID is missing<br></span>";
		return $returnArr;
	}

	$options = ["http" => ["header" => "X-Riot-Token: $RGAPI_Key"]];
	$context = stream_context_create($options);
	$content = file_get_contents("https://europe.api.riotgames.com/lol/match/v5/matches/by-puuid/{$player['PUUID']}/ids?startTime={$tournament_start}&endTime={$tournament_end}&type=tourney&start=0&count=40", false,$context);

	if ($content === FALSE) {
		$returnArr["echo"] .= "<span style='color: orangered'>--could not get Games, request failed: {$http_response_header[0]}<br></span>";
		return $returnArr;
	}
	if (str_contains($http_response_header[0], "200")) {
		$data = json_decode($content, true);
		$game_count = count($data);
		$returnArr["echo"] .= "<span style='color: limegreen'>-got Games: $game_count<br></span>";
		foreach ($data as $game) {
			$game_in_DB = $dbcn->query("SELECT * FROM games WHERE RIOT_matchID = '{$game}'")->fetch_assoc();
			if ($game_in_DB == NULL) {
				$returnArr["echo"] .= "<span style='color: lawngreen'>--write Game $game to DB<br></span>";
				$returnArr["writes"]++;
				// try catch block um abzufangen, dass eine anderer Spieler schneller war
				try {
					$dbcn->query("INSERT INTO games (RIOT_matchID) VALUES ('$game')");
				} catch (Exception $e) {
					$returnArr["echo"] .= "<span style='color: orangered'>----Game $game failed writing to DB (probably already written)<br></span>";
				}
			} else {
				$returnArr["echo"] .= "<span style='color: orange'>--Game $game already in DB<br></span>";
				$returnArr["already"]++;
			}
			if (!in_array($game,$matches_from_player)) {
				$matches_from_player[] = $game;
			}
		}
	} else {
		$response = explode(" ", $http_response_header[0])[1];
		$returnArr["echo"] .= "<span style='color: orangered'>-could not get Games, response-code: $response<br></span>";
	}

	$matches_gotten = json_encode($matches_from_player);
	$dbcn->execute_query("UPDATE players SET matches_gotten = '$matches_gotten' WHERE OPL_ID = ?", [$playerID]);

	return $returnArr;
}

// sendet 1 Anfrage an Riot API (Match-V5)
function add_match_data($RiotMatchID,$tournamentID) {
	$returnArr = array("return"=>0, "echo"=>"", "writes"=>0, "response"=>NULL);
	$dbcn = create_dbcn();
	$RGAPI_Key = get_rgapi_key();
	if ($dbcn -> connect_error){
		$returnArr["return"] = 1;
		$returnArr["echo"] .= "<span style='color: red'>Database Connection failed : " . $dbcn->connect_error . "<br><br></span>";
		return $returnArr;
	}

	$matchDB = $dbcn->query("SELECT * FROM games WHERE RIOT_matchID = '$RiotMatchID'")->fetch_assoc();
	$returnArr["echo"] .= "<span style='color: royalblue'>writing Matchdata for $RiotMatchID :<br></span>";

	if ($matchDB['matchdata'] == NULL) {
		$options = ["http" => ["header" => "X-Riot-Token: $RGAPI_Key"]];
		$context = stream_context_create($options);
		$content = file_get_contents("https://europe.api.riotgames.com/lol/match/v5/matches/{$RiotMatchID}", false,$context);

		if ($content === FALSE) {
			$returnArr["echo"] .= "<span style='color: orangered'>-could not get MatchData, request failed: {$http_response_header[0]}<br></span>";
			return $returnArr;
		}
		if (str_contains($http_response_header[0], "200")) {
			$data = json_decode($content, true);
			$played_at = date('Y-m-d', intval($data["info"]["gameCreation"]/1000));
			$returnArr["echo"] .= "<span style='color: limegreen'>-got MatchData<br></span>";
			$returnArr["echo"] .= "<span style='color: limegreen'>-got time: $played_at<br></span>";
			$returnArr["echo"] .= "<span style='color: lawngreen'>--write MatchData to DB<br></span>";
			$returnArr["writes"]++;
			$dbcn->execute_query("UPDATE games SET matchdata = ?, played_at = ? WHERE RIOT_matchID = ?", [$content, $played_at, $RiotMatchID]);
		} else {
			$response = explode(" ", $http_response_header[0])[1];
			$returnArr["echo"] .= "<span style='color: orangered'>-could not get MatchData, response-code: $response<br></span>";
			$returnArr["response"] = $response;
		}
	} else {
		$returnArr["echo"] .= "<span style='color: orange'>-Matchdata is already in DB<br></span>";
	}

	return $returnArr;
}

// sendet keine Anfrage
function assign_and_filter_game($RiotMatchID,$tournamentID):array {
	$returnArr = array("return"=>0, "echo"=>"", "notUL"=>0, "isUL"=>0, "sorted"=>0, "notsorted"=>0);
	$dbcn = create_dbcn();
	if ($dbcn -> connect_error){
		$returnArr["return"] = 1;
		$returnArr["echo"] .= "<span style='color: red'>Database Connection failed : " . $dbcn->connect_error . "<br><br></span>";
		return $returnArr;
	}

	$top_tournamentID = get_top_parent_tournament($dbcn, $tournamentID);

	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournamentID])->fetch_assoc();

	// check for Data
	$returnArr["echo"] .= "<span style='color: royalblue'>Sorting $RiotMatchID<br></span>";
	$gameDB = $dbcn->execute_query("SELECT * FROM games WHERE RIOT_matchID = ? AND (played_at BETWEEN ? AND ?)", [$RiotMatchID, $tournament["dateStart"], $tournament["dateEnd"]])->fetch_assoc();
	$data = json_decode($gameDB['matchdata'], true);
	if ($data == NULL) {
		$returnArr["echo"] .= "<span style='color: orange'>-Game has no Data<br></span>";
		$returnArr["return"] = 1;
		return $returnArr;
	}

	$game_in_tournament = $dbcn->execute_query("SELECT * FROM games_in_tournament WHERE RIOT_matchID = ? AND OPL_ID_tournament = ?", [$RiotMatchID, $top_tournamentID])->fetch_assoc();
	$game_in_tournament_written = !($game_in_tournament == NULL);

	// check to see if the players make a team in the tournament
	$puuids = $data['metadata']['participants'];
	$blueIDs = array_slice($puuids,0,5);
	$redIDs = array_slice($puuids,5,10);
	$BlueTeamID = get_teamID_by_puuids($dbcn,$blueIDs,$tournamentID);
	$RedTeamID = get_teamID_by_puuids($dbcn,$redIDs,$tournamentID);
	if ($BlueTeamID == NULL) {
		$returnArr["echo"] .= "<span style='color: orange'>-Blue Team is not a Team from Tournament<br></span>";
		$returnArr["echo"] .= "<span style='color: lawngreen'>--write not UL-Game to DB<br></span>";
		$returnArr["notUL"]++;
		if ($game_in_tournament_written) {
			$dbcn->execute_query("UPDATE games_in_tournament SET not_ul_game = TRUE WHERE RIOT_matchID = ? AND OPL_ID_tournament = ?", [$RiotMatchID, $top_tournamentID]);
		} else {
			$dbcn->execute_query("INSERT INTO games_in_tournament (RIOT_matchID, OPL_ID_tournament, not_ul_game) VALUES (?,?,true)", [$RiotMatchID, $top_tournamentID]);
			$game_in_tournament = true;
		}
		return $returnArr;
	} else {
		$BlueTeamName = $dbcn->query("SELECT name FROM teams WHERE OPL_ID = {$BlueTeamID}")->fetch_column();
		$returnArr["echo"] .= "<span style='color: lightblue'>-Blue Team is $BlueTeamName<br></span>";
		$returnArr["echo"] .= "<span style='color: lawngreen'>--write BLueTeamID to DB<br></span>";
		if ($game_in_tournament_written) {
			$dbcn->execute_query("UPDATE games_in_tournament SET OPL_ID_blueTeam = ? WHERE RIOT_matchID = ? AND OPL_ID_tournament = ?", [$BlueTeamID, $RiotMatchID, $top_tournamentID]);
		} else {
			$dbcn->execute_query("INSERT INTO games_in_tournament (RIOT_matchID, OPL_ID_tournament, OPL_ID_blueTeam) VALUES (?,?,?)", [$RiotMatchID, $top_tournamentID, $BlueTeamID]);
			$game_in_tournament = true;
		}
	}
	if ($RedTeamID == NULL) {
		$returnArr["echo"] .= "<span style='color: orange'>-Red Team is not a Team from Tournament<br></span>";
		$returnArr["echo"] .= "<span style='color: lawngreen'>--write not an UL Game to DB<br></span>";
		$returnArr["notUL"]++;
		$dbcn->execute_query("UPDATE games_in_tournament SET not_ul_game = TRUE WHERE RIOT_matchID = ? AND OPL_ID_tournament = ?", [$RiotMatchID, $top_tournamentID]);
		return $returnArr;
	} else {
		$RedTeamName = $dbcn->query("SELECT name FROM teams WHERE OPL_ID = {$RedTeamID}")->fetch_column();
		$returnArr["echo"] .= "<span style='color: lightblue'>-Red Team is $RedTeamName<br></span>";
		$returnArr["echo"] .= "<span style='color: lawngreen'>--write RedTeamID to DB<br></span>";
		$dbcn->execute_query("UPDATE games_in_tournament SET OPL_ID_redTeam = ? WHERE RIOT_matchID = ? AND OPL_ID_tournament = ?", [$RedTeamID, $RiotMatchID, $top_tournamentID]);
	}
	$returnArr["echo"] .= "<span style='color: limegreen'>-Game is from Tournament<br></span>";
	$returnArr["isUL"]++;

	// check from which match the game is
	$matchDB = [];
	if ($tournament["eventType"] == "group") {
		$matchDB = $dbcn->execute_query("SELECT * FROM matchups WHERE ((OPL_ID_team1 = ? AND OPL_ID_team2 = ?) OR (OPL_ID_team1 = ? AND OPL_ID_team2 = ?)) AND OPL_ID_tournament = ?", [$BlueTeamID, $RedTeamID, $RedTeamID, $BlueTeamID, $tournamentID])->fetch_all(MYSQLI_ASSOC);
	} elseif ($tournament["eventType"] == "league") {
		$matchDB = $dbcn->execute_query("SELECT * FROM matchups WHERE ((OPL_ID_team1 = ? AND OPL_ID_team2 = ?) OR (OPL_ID_team1 = ? AND OPL_ID_team2 = ?)) AND OPL_ID_tournament IN (SELECT OPL_ID FROM tournaments WHERE eventType = 'group' AND OPL_ID_parent = ?)", [$BlueTeamID, $RedTeamID, $RedTeamID, $BlueTeamID, $tournamentID])->fetch_all(MYSQLI_ASSOC);
	} elseif ($tournament["eventType"] == "tournament") {
		$matchDB = $dbcn->execute_query("SELECT * FROM matchups
         										WHERE ((OPL_ID_team1 = ? AND OPL_ID_team2 = ?) OR (OPL_ID_team1 = ? AND OPL_ID_team2 = ?))
         										  AND OPL_ID_tournament IN 
         										      (SELECT OPL_ID FROM tournaments
         										                     WHERE (eventType = 'group' AND OPL_ID_parent IN (SELECT OPL_ID FROM tournaments WHERE eventType = 'league' AND OPL_ID_parent = ?))
         										                        OR (eventType = 'playoffs') AND OPL_ID_parent = ?)",
			[$BlueTeamID, $RedTeamID, $RedTeamID, $BlueTeamID, $tournamentID, $tournamentID])->fetch_all(MYSQLI_ASSOC);
	}
	if (count($matchDB) == 0) {
		$returnArr["echo"] .= "<span style='color: orange'>-!no fitting Match found<br></span>";
		$returnArr["notsorted"]++;
	} elseif (count($matchDB) > 0) {
		// TODO: mit gespielter Zeit abstimmen, falls sich mehrfach getroffen wird
		if (count($matchDB) > 1) {
			$returnArr["echo"] .= "<span style='color: orange'>-!more than one Match fits to Teams!<br></span>";
		}
		foreach ($matchDB as $match) {
			$matchID = $match['OPL_ID'];
			$returnArr["echo"] .= "<span style='color: lightblue'>-Game is from Match $matchID<br></span>";
			$game_to_match = $dbcn->execute_query("SELECT * FROM games_to_matches WHERE RIOT_matchID = ? AND OPL_ID_matches = ?", [$RiotMatchID, $matchID])->fetch_assoc();
			if ($game_to_match == NULL) {
				$dbcn->execute_query("INSERT INTO games_to_matches (RIOT_matchID, OPL_ID_matches) VALUES (?, ?)", [$RiotMatchID, $matchID]);
				$returnArr["echo"] .= "<span style='color: lawngreen'>--write MatchID to DB<br></span>";
				$returnArr["sorted"]++;
			} else {
				$returnArr["echo"] .= "<span style='color: orange'>--MatchID is already assigned<br></span>";
			}
		}
	}

	// check which team won
	if ($data['info']['teams'][0]['win']) {
		$winner = $BlueTeamID;
		$returnArr["echo"] .= "<span style='color: lightblue'>-Blue Team won<br></span>";
	} else {
		$winner = $RedTeamID;
		$returnArr["echo"] .= "<span style='color: lightblue'>-Red Team won<br></span>";
	}
	$dbcn->execute_query("UPDATE games_in_tournament SET winningTeam = ? WHERE RIOT_matchID = ? AND OPL_ID_tournament = ?", [$winner, $RiotMatchID, $top_tournamentID]);

	return $returnArr;
}

// sendet keine Anfrage
function get_teamID_by_puuids(mysqli $dbcn, $PUUIDs, $tournamentID) {
	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournamentID])->fetch_assoc();
	$teams = [];
	$team_counts = [];
	foreach ($PUUIDs as $player) {
		$player_data = NULL;
		if ($tournament["eventType"] == "group") {
			$player_data = $dbcn->execute_query("SELECT p.*, pit.OPL_ID_team AS OPL_ID_team FROM players p JOIN players_in_teams pit ON p.OPL_ID = pit.OPL_ID_player JOIN teams_in_tournaments tit on pit.OPL_ID_team = tit.OPL_ID_team WHERE PUUID = ? AND tit.OPL_ID_group = ?", [$player, $tournamentID])->fetch_assoc();
		} elseif ($tournament["eventType"] == "league") {
			$player_data = $dbcn->execute_query("SELECT p.*, pit.OPL_ID_team AS OPL_ID_team FROM players p JOIN players_in_teams pit ON p.OPL_ID = pit.OPL_ID_player JOIN teams_in_tournaments tit on pit.OPL_ID_team = tit.OPL_ID_team WHERE PUUID = ? AND tit.OPL_ID_group IN (SELECT OPL_ID FROM tournaments WHERE eventType = 'group' AND OPL_ID_parent = ?)", [$player, $tournamentID])->fetch_assoc();
		} elseif ($tournament["eventType"] == "tournament") {
			$player_data = $dbcn->execute_query("SELECT p.*, pit.OPL_ID_team AS OPL_ID_team FROM players p JOIN players_in_teams pit ON p.OPL_ID = pit.OPL_ID_player JOIN teams_in_tournaments tit on pit.OPL_ID_team = tit.OPL_ID_team WHERE PUUID = ? AND tit.OPL_ID_group IN (SELECT OPL_ID FROM tournaments WHERE eventType = 'group' AND OPL_ID_parent IN (SELECT OPL_ID FROM tournaments WHERE eventType = 'league' AND OPL_ID_parent = ?))", [$player, $tournamentID])->fetch_assoc();
		}
		if ($player_data == NULL) {
			continue;
		}
		$team = $dbcn->query("SELECT * FROM teams WHERE OPL_ID = {$player_data['OPL_ID_team']}")->fetch_assoc();
		if (in_array($team['OPL_ID'],$teams)) {
			$team_counts[$team['OPL_ID']] += 1;
		} else {
			$teams[] = $team['OPL_ID'];
			$team_counts[$team['OPL_ID']] = 1;
		}
	}
	if (count($teams) == 0) {
		return NULL;
	}
	if (max($team_counts) >= 3) {
		$TeamID = array_keys($team_counts, max($team_counts))[0];
	} else {
		$TeamID = NULL;
	}
	return $TeamID;
}

// sendet 1 Anfrage an Riot API (League-V4)
function get_Rank_by_SummonerId($playerID) {
	$returnArr = array("return"=>0, "echo"=>"", "writes"=>0);
	$dbcn = create_dbcn();
	$RGAPI_Key = get_rgapi_key();

	if ($dbcn -> connect_error){
		$returnArr["return"] = 1;
		$returnArr["echo"] .= "<span style='color: red'>Database Connection failed : " . $dbcn->connect_error . "<br><br></span>";
		return $returnArr;
	}

	$player = $dbcn->query("SELECT * FROM players WHERE players.OPL_ID = {$playerID}")->fetch_assoc();
	$returnArr["echo"] .= "<span style='color: royalblue'>writing Rank for {$player['name']} :<br></span>";

	$today = date("Y-m-d");
	$current_ranked_split = $dbcn->execute_query("SELECT * FROM lol_ranked_splits WHERE split_start < ? AND (split_end > ? OR split_end IS NULL) ORDER BY season DESC, split DESC", [$today, $today])->fetch_assoc();

	if ($player["summonerID"] == NULL) {
		$returnArr["echo"] .= "<span style='color: orangered'>--could not get Rank, SummonerID is missing<br></span>";
		return $returnArr;
	}

	$options = ["http" => ["header" => "X-Riot-Token: $RGAPI_Key"]];
	$context = stream_context_create($options);
	$content = file_get_contents("https://euw1.api.riotgames.com/lol/league/v4/entries/by-summoner/{$player['summonerID']}", false,$context);

	if ($content === FALSE) {
		$returnArr["echo"] .= "<span style='color: orangered'>--could not get Rank, request failed: {$http_response_header[0]}<br></span>";
		return $returnArr;
	}
	if (str_contains($http_response_header[0], "200")) {
		$data = json_decode($content, true);
		$solo_ranked = false;
		for ($i=0; $i<count($data); $i++) {
			if ($data[$i]['queueType'] == "RANKED_SOLO_5x5") {
				$data = $data[$i];
				$solo_ranked = true;
				break;
			}
		}
		if ($solo_ranked) {
			$tier = $data['tier'];
			$div = $data['rank'];
			$league_points = $data['leaguePoints'];
		} else {
			$tier = "UNRANKED";
			$div = NULL;
			$league_points = NULL;
		}
		$returnArr["echo"] .= "<span style='color: limegreen'>--got Rank: $tier $div ($league_points LP)<br></span>";

		if ($current_ranked_split != null) {
			$seasonal_rank = $dbcn->execute_query("SELECT * FROM players_season_rank WHERE OPL_ID_player = ? AND season = ? AND split = ?", [$playerID, $current_ranked_split["season"], $current_ranked_split["split"]])->fetch_assoc();
			if ($seasonal_rank != null) {
				$dbcn->execute_query("UPDATE players_season_rank SET rank_tier = ?, rank_div = ?, rank_LP = ? WHERE OPL_ID_player = ? AND season = ? AND split = ?", [$tier,$div,$league_points,$playerID,$current_ranked_split["season"],$current_ranked_split["split"]]);
			} else {
				$dbcn->execute_query("INSERT INTO players_season_rank (OPL_ID_player, season, split, rank_tier, rank_div, rank_LP) VALUES (?,?,?,?,?,?)", [$playerID,$current_ranked_split["season"],$current_ranked_split["split"],$tier,$div,$league_points]);
			}
		} else {
			$returnArr["echo"] .= "<span style='color: orangered'>--no matching Ranked Split found for today, is today between two splits, or is the current splits date not set?<br></span>";
		}
		$dbcn->query("UPDATE players SET rank_tier = '{$tier}', rank_div = '{$div}', rank_LP = '{$league_points}' WHERE OPL_ID = {$playerID}");

		$returnArr["echo"] .= "<span style='color: lawngreen'>---write Rank to DB<br></span>";
		$returnArr["writes"]++;
	} else {
		$response = explode(" ", $http_response_header[0])[1];
		$returnArr["echo"] .= "<span style='color: orangered'>-could not get Rank, response-code: $response<br></span>";
	}
	return $returnArr;
}

function get_stats_for_players($teamID, $tournamentID) {
	$returnArr = array("return"=>0, "echo"=>"", "writes"=>0);
	$dbcn = create_dbcn();
	if ($dbcn -> connect_error){
		$returnArr["return"] = 1;
		$returnArr["echo"] .= "<span style='color: red'>Database Connection failed : " . $dbcn->connect_error . "<br><br></span>";
		return $returnArr;
	}

	$players = $dbcn->query("SELECT p.*, pit.OPL_ID_team FROM players p JOIN players_in_teams pit on p.OPL_ID = pit.OPL_ID_player WHERE OPL_ID_team = $teamID")->fetch_all(MYSQLI_ASSOC);

	foreach ($players as $player) {
		$games = $dbcn->query("SELECT matchdata FROM games JOIN games_in_tournament git on games.RIOT_matchID = git.RIOT_matchID WHERE ((OPL_ID_blueTeam = {$player['OPL_ID_team']} AND OPL_ID_redTeam IS NOT NULL) OR (OPL_ID_redTeam = {$player['OPL_ID_team']} AND OPL_ID_blueTeam IS NOT NULL ))")->fetch_all(MYSQLI_ASSOC);
		$roles = array("top"=>0,"jungle"=>0,"middle"=>0,"bottom"=>0,"utility"=>0);
		$champions = array();
		foreach ($games as $game) {
			$game_data = json_decode($game['matchdata'],true);
			if (in_array($player['PUUID'],$game_data['metadata']['participants'])) {
				$index = array_search($player['PUUID'],$game_data['metadata']['participants']);
				$position = strtolower($game_data['info']['participants'][$index]['teamPosition']);
				$roles[$position]++;
				$champion = $game_data['info']['participants'][$index]['championName'];
				$win = $game_data['info']['participants'][$index]['win'] ? 1 : 0;
				$kills = $game_data['info']['participants'][$index]['kills'];
				$deaths = $game_data['info']['participants'][$index]['deaths'];
				$assists = $game_data['info']['participants'][$index]['assists'];
				if (array_key_exists($champion,$champions)) {
					$champions[$champion]["games"]++;
					$champions[$champion]["wins"] += $win;
					$champions[$champion]["kills"] += $kills;
					$champions[$champion]["deaths"] += $deaths;
					$champions[$champion]["assists"] += $assists;
				} else {
					$champions[$champion] = array("games"=>1,"wins"=>$win,"kills"=>$kills,"deaths"=>$deaths,"assists"=>$assists);
				}
			}
		}

		$returnArr["echo"] .= "-{$player['summonerName']}:<br>
					--- TOP: {$roles['top']}<br>
					--- JGL: {$roles['jungle']}<br>
					--- MID: {$roles['middle']}<br>
					--- BOT: {$roles['bottom']}<br>
					--- SUP: {$roles['utility']}<br>";
		$roles = json_encode($roles);
		$player_stats_written = $dbcn->execute_query("SELECT * FROM stats_players_teams_tournaments WHERE OPL_ID_player = ? AND OPL_ID_team = ? AND OPL_ID_tournament = ?", [$player["OPL_ID"], $teamID, $tournamentID])->fetch_assoc();
		if ($player_stats_written != NULL) {
			$dbcn->execute_query("UPDATE stats_players_teams_tournaments SET roles = ? WHERE OPL_ID_player = ? AND OPL_ID_team = ? AND OPL_ID_tournament = ?", [$roles, $player['OPL_ID'], $teamID, $tournamentID]);
		} else {
			$dbcn->execute_query("INSERT INTO stats_players_teams_tournaments (OPL_ID_player, OPL_ID_team, OPL_ID_tournament, roles) VALUES (?, ?, ?, ?)", [$player["OPL_ID"], $teamID, $tournamentID, $roles]);
		}

		$uniq = count($champions);
		$champions = json_encode($champions);
		$returnArr["echo"] .= "-{$player['summonerName']}: $uniq versch. Champs <br>";
		$returnArr["echo"] .= "--$champions<br>";
		$player_stats_written = $dbcn->execute_query("SELECT * FROM stats_players_teams_tournaments WHERE OPL_ID_player = ? AND OPL_ID_team = ? AND OPL_ID_tournament = ?", [$player["OPL_ID"], $teamID, $tournamentID])->fetch_assoc();
		if ($player_stats_written != NULL) {
			$dbcn->execute_query("UPDATE stats_players_teams_tournaments SET champions = ? WHERE OPL_ID_player = ? AND OPL_ID_team = ? AND OPL_ID_tournament = ?", [$champions, $player['OPL_ID'], $teamID, $tournamentID]);
		} else {
			$dbcn->execute_query("INSERT INTO stats_players_teams_tournaments (OPL_ID_player, OPL_ID_team, OPL_ID_tournament, champions) VALUES (?, ?, ?, ?)", [$player["OPL_ID"], $teamID, $tournamentID, $champions]);
		}

		$returnArr["writes"]++;
	}
	return $returnArr;
}

function calculate_avg_team_rank($teamID, $tournamentID=null):array {
	$returnArr = array("return"=>0, "echo"=>"", "writes"=>0);
	$dbcn = create_dbcn();
	if ($dbcn -> connect_error){
		$returnArr["return"] = 1;
		$returnArr["echo"] .= "<span style='color: red'>Database Connection failed : " . $dbcn->connect_error . "<br><br></span>";
		return $returnArr;
	}
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

	if ($tournamentID != null) {
		$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournamentID])->fetch_assoc();
		$ranked_split = ["season"=>$tournament["ranked_season"], "split"=>$tournament["ranked_split"]];
		$next_split = $dbcn->execute_query("SELECT * FROM lol_ranked_splits WHERE (season = ? AND split = ?)",[$ranked_split["season"], $ranked_split["split"]+1])->fetch_assoc();
		if ($next_split == null) {
			$next_split = $dbcn->execute_query("SELECT * FROM lol_ranked_splits WHERE (season = ? AND split = ?)",[$ranked_split["season"]+1, 1])->fetch_assoc();
		}
		if ($next_split == null) $returnArr["echo"] .= "<span style='color: orangered'>- kein zweiter Split gefunden</span><br>";

		$team_season_rank = $dbcn->execute_query("SELECT * FROM teams_tournament_rank WHERE OPL_ID_team = ? AND OPL_ID_tournament = ? AND second_ranked_split = false", [$teamID, $tournamentID])->fetch_assoc();
		$team_season_rank_2 = $dbcn->execute_query("SELECT * FROM teams_tournament_rank WHERE OPL_ID_team = ? AND OPL_ID_tournament = ? AND second_ranked_split = true", [$teamID, $tournamentID])->fetch_assoc();
		$players = $dbcn->execute_query("SELECT * FROM players p JOIN players_in_teams_in_tournament pit on p.OPL_ID = pit.OPL_ID_player LEFT JOIN players_season_rank psr ON psr.OPL_ID_player = p.OPL_ID AND psr.season = ? AND psr.split = ? WHERE OPL_ID_team = ? AND OPL_ID_tournament = ?", [$ranked_split["season"], $ranked_split["split"], $teamID, $tournamentID])->fetch_all(MYSQLI_ASSOC);
		if ($next_split != null) $players_2 = $dbcn->execute_query("SELECT * FROM players p JOIN players_in_teams_in_tournament pit on p.OPL_ID = pit.OPL_ID_player LEFT JOIN players_season_rank psr ON psr.OPL_ID_player = p.OPL_ID AND psr.season = ? AND psr.split = ? WHERE OPL_ID_team = ? AND OPL_ID_tournament = ?", [$next_split["season"], $next_split["split"], $teamID, $tournamentID])->fetch_all(MYSQLI_ASSOC);
	} else {
		$players = $dbcn->execute_query("SELECT * FROM players p JOIN players_in_teams pit on p.OPL_ID = pit.OPL_ID_player WHERE OPL_ID_team = ?", [$teamID])->fetch_all(MYSQLI_ASSOC);

		$today = date("Y-m-d");
		$ranked_split = $dbcn->execute_query("SELECT * FROM lol_ranked_splits WHERE split_start < ? AND (split_end > ? OR split_end IS NULL) ORDER BY season DESC, split DESC", [$today, $today])->fetch_assoc();
	}

	$rank_arr = [];
	foreach ($players as $player) {
		if ($player['rank_tier'] != NULL && $player['rank_tier'] != "UNRANKED") {
			if ($player['rank_tier'] === "MASTER" || $player['rank_tier'] === "GRANDMASTER" || $player['rank_tier'] === "CHALLENGER") {
				$player_rank = $ranks[$player['rank_tier']];
			} else {
				$player_rank = $ranks[$player['rank_tier']." ".$player['rank_div']];
			}
			$rank_arr[] = $player_rank;
		}
	}
	if (count($rank_arr) == 0) {
		// Team hat keinen avg Rang
		if ($tournamentID != null) {
			// Rang wurde für Team in einem Turnier berechnet
			if ($team_season_rank != null) {
				$dbcn->execute_query("UPDATE teams_tournament_rank SET avg_rank_tier = NULL, avg_rank_div = NULL, avg_rank_num = NULL WHERE OPL_ID_team = ? AND OPL_ID_tournament = ? AND second_ranked_split = false", [$teamID, $tournament["OPL_ID"]]);
			} else {
				$dbcn->execute_query("INSERT INTO teams_tournament_rank (OPL_ID_team, OPL_ID_tournament, second_ranked_split, avg_rank_tier, avg_rank_div, avg_rank_num) VALUES (?,?,false,null,null,null)", [$teamID, $tournament["OPL_ID"]]);
			}
			$returnArr["echo"] .= "- ".$tournamentID."_S".$ranked_split["season"]."-".$ranked_split["split"].": kein Rang<br>";
		} else {
			// Rang wurde für Team aktuell berechnet
			$dbcn->execute_query("UPDATE teams SET avg_rank_tier = NULL, avg_rank_div = NULL, avg_rank_num = NULL WHERE OPL_ID = ?", [$teamID]);
			$returnArr["echo"] .= "- Aktuell: kein Rang<br>";
		}
	} else {
		$rank = 0;
		foreach ($rank_arr as $player) {
			$rank += $player;
		}
		$rank_num = $rank / count($rank_arr);
		$rank = floor($rank_num);
		if ($tournamentID != null) {
			// Rang wurde für Team in einem Turnier berechnet
			if ($team_season_rank != null) {
				$dbcn->execute_query("UPDATE teams_tournament_rank SET avg_rank_tier = ?, avg_rank_div = ?, avg_rank_num = ? WHERE OPL_ID_team = ? AND OPL_ID_tournament = ? AND second_ranked_split = false", [$ranks_rev[$rank][0], $ranks_rev[$rank][1], $rank_num, $teamID, $tournament["OPL_ID"]]);
			} else {
				$dbcn->execute_query("INSERT INTO teams_tournament_rank (OPL_ID_team, OPL_ID_tournament, second_ranked_split, avg_rank_tier, avg_rank_div, avg_rank_num) VALUES (?,?,false,?,?,?)", [$teamID, $tournament["OPL_ID"], $ranks_rev[$rank][0], $ranks_rev[$rank][1], $rank_num]);
			}
			$returnArr["echo"] .= "- ".$tournamentID."_S".$ranked_split["season"]."-".$ranked_split["split"].": ". $ranks_rev[$rank][0] . $ranks_rev[$rank][1] . " " . $rank_num."<br>";
		} else {
			// Rang wurde für Team aktuell berechnet
			$dbcn->execute_query("UPDATE teams SET avg_rank_tier = ?, avg_rank_div = ?, avg_rank_num = ? WHERE OPL_ID = ?", [$ranks_rev[$rank][0], $ranks_rev[$rank][1], $rank_num, $teamID]);
			$returnArr["echo"] .= "- Aktuell: ". $ranks_rev[$rank][0] . $ranks_rev[$rank][1] . " " . $rank_num."<br>";
		}
		$returnArr["writes"] = 1;
	}

	if ($tournamentID != null && $next_split != null) {
		$rank_arr = [];
		foreach ($players_2 as $player) {
			if ($player['rank_tier'] != NULL && $player['rank_tier'] != "UNRANKED") {
				if ($player['rank_tier'] === "MASTER" || $player['rank_tier'] === "GRANDMASTER" || $player['rank_tier'] === "CHALLENGER") {
					$player_rank = $ranks[$player['rank_tier']];
				} else {
					$player_rank = $ranks[$player['rank_tier']." ".$player['rank_div']];
				}
				$rank_arr[] = $player_rank;
			}
		}
		if (count($rank_arr) == 0) {
			if ($team_season_rank_2 != null) {
				$dbcn->execute_query("UPDATE teams_tournament_rank SET avg_rank_tier = NULL, avg_rank_div = NULL, avg_rank_num = NULL WHERE OPL_ID_team = ? AND OPL_ID_tournament = ? AND second_ranked_split = true", [$teamID, $tournament["OPL_ID"]]);
			} else {
				$dbcn->execute_query("INSERT INTO teams_tournament_rank (OPL_ID_team, OPL_ID_tournament, second_ranked_split, avg_rank_tier, avg_rank_div, avg_rank_num) VALUES (?,?,true,null,null,null)", [$teamID, $tournament["OPL_ID"]]);
			}
			$returnArr["echo"] .= "- ".$tournamentID."_S".$next_split["season"]."-".$next_split["split"].": kein Rang<br>";
		} else {
			$rank = 0;
			foreach ($rank_arr as $player) {
				$rank += $player;
			}
			$rank_num = $rank / count($rank_arr);
			$rank = floor($rank_num);
			if ($team_season_rank_2 != null) {
				$dbcn->execute_query("UPDATE teams_tournament_rank SET avg_rank_tier = ?, avg_rank_div = ?, avg_rank_num = ? WHERE OPL_ID_team = ? AND OPL_ID_tournament = ? AND second_ranked_split = true", [$ranks_rev[$rank][0], $ranks_rev[$rank][1], $rank_num, $teamID, $tournament["OPL_ID"]]);
			} else {
				$dbcn->execute_query("INSERT INTO teams_tournament_rank (OPL_ID_team, OPL_ID_tournament, second_ranked_split, avg_rank_tier, avg_rank_div, avg_rank_num) VALUES (?,?,true,?,?,?)", [$teamID, $tournament["OPL_ID"], $ranks_rev[$rank][0], $ranks_rev[$rank][1], $rank_num]);
			}
			$returnArr["writes"] = 1;
			$returnArr["echo"] .= "- ".$tournamentID."_S".$next_split["season"]."-".$next_split["split"].": ". $ranks_rev[$rank][0] . $ranks_rev[$rank][1] . " " . $rank_num."<br>";
		}

	}

	return $returnArr;
}

function calculate_teamstats($teamID, $tournamentID) {
	$returnArr = array("return"=>0, "echo"=>"", "writes"=>0, "updates"=>0, "without"=>0);
	$dbcn = create_dbcn();
	if ($dbcn -> connect_error){
		$returnArr["return"] = 1;
		$returnArr["echo"] .= "<span style='color: red'>Database Connection failed : " . $dbcn->connect_error . "<br><br></span>";
		return $returnArr;
	}
	$games = $dbcn->execute_query("SELECT matchdata, OPL_ID_blueTeam, OPL_ID_redTeam FROM games JOIN games_in_tournament git on games.RIOT_matchID = git.RIOT_matchID WHERE (OPL_ID_blueTeam = ? AND OPL_ID_redTeam IS NOT NULL) OR (OPL_ID_blueTeam IS NOT NULL AND OPL_ID_redTeam = ?)", [$teamID, $teamID])->fetch_all(MYSQLI_ASSOC);
	$games_played = count($games);

	if ($games_played == 0) {
		$returnArr["echo"] .= "Team ".$teamID." has not played any Games<br>";
		$returnArr["without"]++;
		return $returnArr;
	}

	$champs_played = $champs_played_against = $bans = $bans_against = array();

	$wins = 0;
	$win_time = 0;

	$ddragon_dir = new DirectoryIterator(__DIR__."/../../ddragon");
	$patches = [];

	foreach ($ddragon_dir as $patch_dir) {
		if (!$patch_dir->isDot() && $patch_dir->getFilename() != "img" && $patch_dir->isDir()) {
			$patches[] = $patch_dir->getFilename();
		}
	}
	usort($patches, "version_compare");
	$latest_patch = end($patches);
	$champion_data = json_decode(file_get_contents(__DIR__."/../../ddragon/$latest_patch/data/champion.json"),true)['data'];
	$champions_by_key = [];
	foreach ($champion_data as $champ) {
		$champions_by_key[$champ['key']] = $champ['id'];
	}

	foreach ($games as $gindex=>$game) {
		$game_data = json_decode($game['matchdata'],true);
		if ($game['OPL_ID_blueTeam'] == $teamID) {
			$side = 0;
			$side_a = 1;
		} elseif ($game['OPL_ID_redTeam'] == $teamID) {
			$side = 1;
			$side_a = 0;
		} else {
			continue;
		}
		// wins and win_time
		$game_win = 0;
		if ($game_data['info']['teams'][$side]['win']) {
			$wins++;
			$win_time += $game_data['info']['gameDuration'];
			$game_win = 1;
		}
		// played champs
		for ($i = $side*5; $i < $side*5+5; $i++) {
			$champ = $game_data['info']['participants'][$i]['championName'];
			if (array_key_exists($champ,$champs_played)) {
				$champs_played[$champ]["games"]++;
				$champs_played[$champ]["wins"] += $game_win;
			} else {
				$champs_played[$champ] = array("games"=>1,"wins"=>$game_win);
			}
		}
		for ($i = $side_a*5; $i < $side_a*5+5; $i++) {
			$champ = $game_data['info']['participants'][$i]['championName'];
			if (array_key_exists($champ,$champs_played_against)) {
				$champs_played_against[$champ]++;
			} else {
				$champs_played_against[$champ] = 1;
			}
		}
		// banned champs
		$game_bans = $game_data['info']['teams'][$side]['bans'];
		foreach ($game_bans as $game_ban) {
			$champ = $champions_by_key[$game_ban['championId']];
			if (array_key_exists($champ,$bans)) {
				$bans[$champ]++;
			} else {
				$bans[$champ] = 1;
			}
		}
		$game_bans_against = $game_data['info']['teams'][$side_a]['bans'];
		foreach ($game_bans_against as $game_ban) {
			$champ = $champions_by_key[$game_ban['championId']];
			if (array_key_exists($champ,$bans_against)) {
				$bans_against[$champ]++;
			} else {
				$bans_against[$champ] = 1;
			}
		}

	}
	if ($wins != 0) {
		$avg_win_time = $win_time / $wins;
	} else {
		$avg_win_time = 0;
	}

	$champs_played = json_encode($champs_played);
	$bans = json_encode($bans);
	$champs_played_against = json_encode($champs_played_against);
	$bans_against = json_encode($bans_against);

	$teamstats = $dbcn->query("SELECT * FROM stats_teams_in_tournaments WHERE OPL_ID_team = $teamID AND OPL_ID_tournament = $tournamentID")->fetch_assoc();
	if ($teamstats == NULL) {
		$dbcn->query("INSERT INTO stats_teams_in_tournaments
    		(OPL_ID_team, OPL_ID_tournament, champs_played, champs_banned, champs_played_against, champs_banned_against, games_played, games_won, avg_win_time)
			VALUES 
			($teamID, $tournamentID, '$champs_played', '$bans', '$champs_played_against', '$bans_against', $games_played, $wins, $avg_win_time)");
		$returnArr["echo"] .= "writing Stats for Team ".$teamID."<br>";
		$returnArr["writes"]++;
	} else {
		$dbcn->query("UPDATE stats_teams_in_tournaments SET champs_played='$champs_played', champs_banned='$bans', champs_played_against='$champs_played_against', champs_banned_against='$bans_against', games_played=$games_played, games_won=$wins, avg_win_time=$avg_win_time WHERE OPL_ID_team = $teamID AND OPL_ID_tournament = $tournamentID");
		$returnArr["echo"] .= "updating Stats for Team $teamID in $tournamentID<br>";
		$returnArr["updates"]++;
	}

	return $returnArr;
}