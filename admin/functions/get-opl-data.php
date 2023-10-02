<?php
$root = __DIR__."/../../";
include_once $root."setup/data.php";

function get_tournament($id):array {
	$returnArr = ["info"=>"", "data"=>[], "button"=>""];
	$dbcn = create_dbcn();
	$bearer_token = get_opl_bearer_token();
	$user_agent = get_user_agent_for_api_calls();

	if ($dbcn -> connect_error){
		$returnArr["info"] .= "<span style='color: red'>Database Connection failed : " . $dbcn->connect_error . "<br></span>";
		return $returnArr;
	}

	$url = "https://www.opleague.pro/api/v4/tournament/$id";
	$options = ["http" => [
		"header" => [
			"Authorization: Bearer $bearer_token",
			"User-Agent: $user_agent",
		]
	]];
	$context = stream_context_create($options);
	$response = json_decode(file_get_contents($url, false, $context),true);

	$data = $response["data"];

	$name = $data["name"];
	$name_lower = strtolower($name);
	$dateStart = $data["start_on"]["date"] ?? NULL;
	$dateEnd = $data["end_on"]["date"] ?? NULL;
	$logo_url = $data["logo_array"]["background"] ?? NULL;
	$logo_id = ($logo_url != NULL) ? explode("/", $logo_url, -1) : NULL;
	$logo_id = ($logo_id != NULL) ? end($logo_id) : NULL;

	$split = NULL;
	$season = NULL;
	if (str_contains($name_lower,"sommer")){
		$split = "sommer";
	} elseif (str_contains($name_lower,"winter")){
		$split = "winter";
	} else {
		$returnArr["info"] .= "<span style='color: orangered'>Keine Sommer/Winterseason gefunden <br></span>";
	}

	if (preg_match("/(?:winter|sommer) *([0-9]{2})/",$name_lower,$season_match)) {
		$season = $season_match[1];
	}

	$type = NULL;
	if (str_contains($name_lower, "wildcard")) {
		$type = "wildcard";
	} elseif (preg_match("/playoffs?/",$name_lower)) {
		$type = "playoffs";
	} elseif (str_contains($name_lower, "gruppe")) {
		$type = "group";
	} elseif (str_contains($name_lower, " liga")) {
		$type = "league";
	} else {
		$type = "tournament";
	}

	// regex prüft ob name mit einer zahl oder zwei zahlen mit - oder / getrennt endet
	$number_matches = [];
	$number = $numberTo = NULL;
	if (preg_match("#[0-9]([-/][0-9])?$#",$name,$number_matches) && $type != "tournament") {
		$number = $number_matches[0];
		if (strlen($number) > 1) {
			$numberTo = substr($number,2,1);
			$number = substr($number,0,1);
		}
	} else {
		$returnArr["info"] .= "<span style='color: orangered'>Keine Nummer gefunden <br></span>";
	}

	$groups_league_num = NULL;
	if ($type == "group") {
		if (preg_match("/(liga) ?([0-9]+)/",$name_lower,$group_leagues_matches)) {
			$groups_league_num = $group_leagues_matches[2];
		}
	}

	if ($type == "group") {
		$suggested_parent = $dbcn->execute_query("SELECT OPL_ID FROM tournaments WHERE eventType = 'league' AND season = ? AND split = ? AND number = ? ORDER BY OPL_ID", [$season, $split, $groups_league_num])->fetch_column();
	} else {
		$suggested_parent = $dbcn->execute_query("SELECT OPL_ID FROM tournaments WHERE eventType = 'tournament' AND season = ? AND split = ? ORDER BY OPL_ID", [$season, $split])->fetch_column();
	}

	$returnArr["data"] = [
		"OPL_ID" => $id,
		"OPL_ID_parent" => $suggested_parent,
		"name" => $name,
		"split" => $split,
		"season" => $season,
		"format" => NULL,
		"eventType" => $type,
		"number" => $number,
		"numberRangeTo" => $numberTo,
		"dateStart" => $dateStart,
		"dateEnd" => $dateEnd,
		"OPL_logo_url" => $logo_url,
		"OPL_ID_logo" => $logo_id,
		"finished" => false,
		"deactivated" => true,
	];

	$returnArr["info"] .= "
			ID: $id <br>
			name: $name <br>
			Split: $split <br>
			Season: $season <br>
			Typ: $type <br>
			Num: $number <br>
			NumRangeTo: $numberTo <br>
			von: $dateStart <br>
			bis: $dateEnd <br>
			logo: <a href='https://www.opleague.pro/$logo_url'>$logo_id</a>";

	$returnArr["button"] = create_tournament_get_button($returnArr["data"], true);

	return $returnArr;
}

function write_tournament(array $data):string {
	$dbcn = create_dbcn();
	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$data["OPL_ID"]])->fetch_assoc();

	$returnInfo = "Schreibe Turnier {$data["OPL_ID"]} - {$data["name"]} in die Datenbank<br>";

	foreach ($data as $key=>$item) {
		if ($item == "") {
			$data[$key] = NULL;
		}
	}

	if ($tournament == NULL) {
		$returnInfo .= "<span style='color: lawngreen'>- Turnier ist noch nicht in DB, schreibe in DB<br></span>";
		$dbcn->execute_query("INSERT INTO
			tournaments (OPL_ID, OPL_ID_parent, name, split, season, eventType, format, number, numberRangeTo, dateStart, dateEnd, OPL_logo_url, OPL_ID_logo, finished, deactivated)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [$data["OPL_ID"], $data["OPL_ID_parent"], $data["name"], $data["split"], $data["season"], $data["eventType"], $data["format"], $data["number"], $data["numberRangeTo"], $data["dateStart"], $data["dateEnd"], $data["OPL_logo_url"], $data["OPL_ID_logo"], $data["finished"], $data["deactivated"]]);
	} else {
		$changed = false;
		foreach ($tournament as $key=>$item) {
			if ($data[$key] != $item) {
				$returnInfo .= "- $key wurde geändert<br>";
				$changed = true;
			}
		}
		if ($changed) {
			$returnInfo .= "<span style='color: yellow'>- Turnier wurde geupdatet, aktualisiere DB<br></span>";
			$dbcn->execute_query("UPDATE tournaments SET OPL_ID_parent = ?, name = ?, split = ?, season = ?, eventType = ?, format = ?, number = ?, numberRangeTo = ?, dateStart = ?, dateEnd = ?, OPL_logo_url = ?, OPL_ID_logo = ?, finished = ?, deactivated = ? WHERE OPL_ID = ?", [$data["OPL_ID_parent"], $data["name"], $data["split"], $data["season"], $data["eventType"], $data["format"], $data["number"], $data["numberRangeTo"], $data["dateStart"], $data["dateEnd"], $data["OPL_logo_url"], $data["OPL_ID_logo"], $data["finished"], $data["deactivated"], $data["OPL_ID"]]);
		} else {
			$returnInfo .= "- neue Daten identisch zu vorhandenen Daten<br>";
		}

	}

	$local_img_folder_path = __DIR__."/../../img/tournament_logos";
	if ($data["OPL_logo_url"] != NULL && !file_exists("$local_img_folder_path/{$data["OPL_ID_logo"]}/logo.webp")) {
		if (!is_dir("$local_img_folder_path/{$data["OPL_ID_logo"]}")) {
			mkdir("$local_img_folder_path/{$data["OPL_ID_logo"]}");
		}
		$opl_logo_url = "/styles/media/event/{$data["OPL_ID_logo"]}/Logo_100.webp";

		$user_agent = get_user_agent_for_api_calls();
		$options = ["http" => [
			"header" => [
				"User-Agent: $user_agent",
			]
		]];
		$context = stream_context_create($options);
		$img_response = imagecreatefromstring(file_get_contents("https://www.opleague.pro$opl_logo_url", false, $context));

		$img = $img_response;
		if ($img) {
			imagepalettetotruecolor($img);
			imagealphablending($img, false);
			imagesavealpha($img, true);
			imagewebp($img, "$local_img_folder_path/{$data["OPL_ID_logo"]}/logo.webp", 100);
			imagedestroy($img);
			$returnInfo .= "--Logo heruntergeladen<br>";
		}
	}

	return $returnInfo;
}

function create_tournament_get_button(array $data, bool $in_write_popup = false):string {
	$id_class = ($in_write_popup) ? "write-popup" : $data["OPL_ID"];

	$result = "<div class='tournament-write-data-wrapper'>";

	$result .= "<div class='tournament-write-data $id_class' data-id='$id_class'>";

	$splitselect_winter = (strtolower($data["split"]) == "winter") ? "selected" : "";
	$splitselect_sommer = (strtolower($data["split"]) == "sommer") ? "selected" : "";

	$typeselect_tournament = (strtolower($data["eventType"]) == "tournament") ? "selected" : "";
	$typeselect_league = (strtolower($data["eventType"]) == "league") ? "selected" : "";
	$typeselect_group = (strtolower($data["eventType"]) == "group") ? "selected" : "";
	$typeselect_wildcard = (strtolower($data["eventType"]) == "wildcard") ? "selected" : "";
	$typeselect_playoffs = (strtolower($data["eventType"]) == "playoffs") ? "selected" : "";

	$formatselect_round_robin = (strtolower($data["format"]) == "round-robin") ? "selected" : "";
	$formatselect_single_elim = (strtolower($data["format"]) == "single-elimination") ? "selected" : "";
	$formatselect_double_elim = (strtolower($data["format"]) == "double-elimination") ? "selected" : "";

	$dateStart = explode(" ",$data["dateStart"])[0];
	$dateEnd = explode(" ",$data["dateEnd"])[0];

	$deactivated_check = ($data["deactivated"]) ? "" : "checked";
	$finished_check = ($data["finished"]) ? "checked" : "";

	$result .= "<label class=\"write_tournament_name\">Name:<input type=\"text\" value=\"{$data["name"]}\" readonly></label>
					<label class=\"write_tournament_id\">ID:<input type=\"text\" value=\"{$data["OPL_ID"]}\" readonly></label>
					<label class=\"write_tournament_parent\">Parent:<input type=\"text\" value=\"{$data["OPL_ID_parent"]}\"></label>
					<label class=\"write_tournament_split\">
						Split:<span class=\"slct\">
							<select>
								<option disabled hidden value=''>nicht erkannt</option>
								<option $splitselect_winter value='winter'>Winter</option>
								<option $splitselect_sommer value='sommer'>Sommer</option>
							</select>
							<span class='material-symbol'>".file_get_contents(__DIR__."/../../icons/material/arrow_drop_down.svg") ."</span>
						</span>
					</label>
					<label class=\"write_tournament_season\">Season:<input type=\"number\" value=\"{$data["season"]}\"></label>
					<label class=\"write_tournament_type\">
						Typ:<span class=\"slct\">
							<select>
								<option value=''></option>
								<option $typeselect_tournament value='tournament'>Turnier</option>
								<option $typeselect_league value='league'>Liga</option>
								<option $typeselect_group value='group'>Gruppe</option>
								<option $typeselect_playoffs value='playoffs'>Playoffs</option>
								<option $typeselect_wildcard value='wildcard'>Wildcard</option>
							</select>
							<span class='material-symbol'>".file_get_contents(__DIR__."/../../icons/material/arrow_drop_down.svg")."</span>
						</span>
					</label>
					<label class=\"write_tournament_format\">
						Format:<span class=\"slct\">
							<select>
								<option value=''></option>
								<option $formatselect_round_robin value='round-robin'>round-robin</option>
								<option $formatselect_single_elim value='single-elimination'>single-elim</option>
								<option $formatselect_double_elim value='double-elimination'>double-elim</option>
							</select>
							<span class='material-symbol'>".file_get_contents(__DIR__."/../../icons/material/arrow_drop_down.svg")."</span>
						</span>
					</label>
					<label class=\"write_tournament_number\">Nummer:<input type=\"number\" value=\"{$data["number"]}\"></label>
					<label class=\"write_tournament_number2\">Nummer2:<input type=\"number\" value=\"{$data["numberRangeTo"]}\"></label>
					<label class=\"write_tournament_startdate\">Start:<input type=\"date\" value=\"{$dateStart}\"></label>
					<label class=\"write_tournament_enddate\">End:<input type=\"date\" value=\"{$dateEnd}\"></label>
					<label class=\"write_tournament_show\">Anzeigen:<input type=\"checkbox\" $deactivated_check></label>
					<label class=\"write_tournament_finished\">Beendet:<input type=\"checkbox\" $finished_check></label>
					<label class=\"write_tournament_logoid\">Logo-ID:<input type=\"number\" value=\"{$data["OPL_ID_logo"]}\" readonly></label>
					<label class=\"write_tournament_logourl\">Logo-URL:<input type=\"text\" value=\"{$data["OPL_logo_url"]}\" readonly></label>";

	$result .= "</div>";
	if ($in_write_popup) $result .= "<button id=\"write_tournament\" type=\"button\">Eintragen</button>";
	if (!$in_write_popup) $result .= "<button class=\"update_tournament\" type=\"button\">Aktualisieren</button>";

	if (!$in_write_popup) $result .= "
			<dialog class='tournament-data-popup dismissable-popup $id_class'>
				<div class='dialog-content'>
					<h2>{$data["name"]} ({$data["eventType"]})</h2>
					<button class='get-teams $id_class' data-id='$id_class'><span>Teams im Turnier updaten</span></button>
					<button class='get-players $id_class' data-id='$id_class'><span>Spieler im Turnier updaten</span></button>
					<button class='get-summoners $id_class' data-id='$id_class'><span>Spieler-Accounts im Turnier updaten</span></button>
					<button class='get-matchups $id_class' data-id='$id_class'><span>Matches im Turnier updaten</span></button>
					<button class='get-results $id_class' data-id='$id_class'><span>Match-Ergebnisse im Turnier updaten</span></button>
					<button class='calculate-standings $id_class' data-id='$id_class'><span>Tabelle des Turniers aktualisieren</span></button>
				</div>
			</dialog>";
	if (!$in_write_popup) $result .= "<button class=\"open-tournament-data-popup $id_class\" type=\"button\" data-id='$id_class'>weitere Daten holen</button>";

	$result .= "</div>";
	return $result;
}

function get_teams_for_tournament($tournamentID):array {
	$returnArr = [];
	$dbcn = create_dbcn();
	$bearer_token = get_opl_bearer_token();
	$user_agent = get_user_agent_for_api_calls();

	if ($dbcn -> connect_error){
		return $returnArr;
	}

	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournamentID])->fetch_assoc();

	if ($tournament["eventType"] != "group") {
		return [];
	}

	$url = "https://www.opleague.pro/api/v4/tournament/$tournamentID/team_registrations";
	$options = ["http" => [
		"header" => [
			"Authorization: Bearer $bearer_token",
			"User-Agent: $user_agent",
		]
	]];
	$context = stream_context_create($options);
	$response = json_decode(file_get_contents($url, false, $context),true);

	$data = $response["data"]["team_registrations"];

	// alle teams vom API-Ergebnis durchgehen
	foreach ($data as $team) {
		// Logo-ID aus url herausholen
		$logo_url = $team["logo_array"]["background"] ?? NULL;
		$logo_id = ($logo_url != NULL) ? explode("/", $logo_url, -1) : NULL;
		$logo_id = ($logo_id != NULL) ? end($logo_id) : NULL;

		// alle nötigen Daten über das team sammeln
		$team_data = [
			"OPL_ID" => $team["ID"],
			"name" => $team["name"],
			"shortName" => $team["short_name"],
			"OPL_logo_url" => $logo_url,
			"OPL_ID_logo" => $logo_id,
		];

		$updated = [];
		$written = $logo_dl = false;

		// in Datenbank nach Team suchen
		$teamDB = $dbcn->execute_query("SELECT * FROM teams WHERE OPL_ID = ?", [$team_data["OPL_ID"]])->fetch_assoc();

		// Team in Datenbank eintragen oder aktualisieren
		if ($teamDB == NULL) {
			$written = true;
			$dbcn->execute_query("INSERT INTO teams (OPL_ID, name, shortName, OPL_logo_url, OPL_ID_logo)
										VALUES (?, ?, ?, ?, ?)", [$team_data["OPL_ID"], $team_data["name"], $team_data["shortName"], $team_data["OPL_logo_url"], $team_data["OPL_ID_logo"]]);
		} else {
			foreach ($team_data as $key=>$item) {
				if ($teamDB[$key] != $item) {
					$updated[$key] = ["old"=>$teamDB[$key], "new"=>$item];
				}
			}
			if (count($updated) > 0) {
				$dbcn->execute_query("UPDATE teams SET name=?, shortName=?, OPL_ID_logo=?, OPL_logo_url=? WHERE OPL_ID = ?", [$team_data["name"], $team_data["shortName"], $team_data["OPL_ID_logo"], $team_data["OPL_logo_url"], $team_data["OPL_ID"]]);
			}
		}

		// Team in Tournament Beziehung prüfen
		$team_in_tournament = $dbcn->execute_query("SELECT * FROM teams_in_tournaments WHERE OPL_ID_team = ? AND OPL_ID_tournament = ?", [$team_data["OPL_ID"], $tournamentID])->fetch_assoc();
		if ($team_in_tournament == NULL) {
			$updated["tournamentID"] = ["old"=>NULL, "new"=>$tournamentID];
			$dbcn->execute_query("INSERT INTO teams_in_tournaments (OPL_ID_team, OPL_ID_tournament) VALUES (?, ?)", [$team_data["OPL_ID"], $tournamentID]);
		}

		// Team Logo herunterladen, wenn es noch nicht existiert
		$local_img_folder_path = __DIR__."/../../img/team_logos";
		if ($team_data["OPL_logo_url"] != NULL && !file_exists("$local_img_folder_path/{$team_data["OPL_ID_logo"]}/logo.webp")) {
			if (!is_dir("$local_img_folder_path/{$team_data["OPL_ID_logo"]}")) {
				mkdir("$local_img_folder_path/{$team_data["OPL_ID_logo"]}");
			}

			$opl_logo_url = "/styles/media/team/{$team_data["OPL_ID_logo"]}/Logo_100.webp";

			$options = ["http" => [
				"header" => [
					"User-Agent: $user_agent",
				]
			]];
			$context = stream_context_create($options);
			$img_response = imagecreatefromstring(file_get_contents("https://www.opleague.pro$opl_logo_url", false, $context));

			$img = $img_response;
			if ($img) {
				imagepalettetotruecolor($img);
				imagealphablending($img, false);
				imagesavealpha($img, true);
				imagewebp($img, "$local_img_folder_path/{$team_data["OPL_ID_logo"]}/logo.webp", 100);
				imagedestroy($img);
				$logo_dl = true;
			}
		}

		$returnArr[] = [
			"team" => $team_data,
			"written" => $written,
			"updated" => $updated,
			"logo_downloaded" => $logo_dl,
		];
	}

	$dbcn->close();
	return $returnArr;
}

// works only for tournaments with eventType group
function get_players_for_tournament($tournamentID):array {
	$returnArr = [];
	$dbcn = create_dbcn();

	if ($dbcn -> connect_error){
		return $returnArr;
	}

	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournamentID])->fetch_assoc();

	if ($tournament["eventType"] != "group") {
		return [];
	}

	$teams = $dbcn->execute_query("SELECT * FROM teams_in_tournaments WHERE OPL_ID_tournament = ?", [$tournamentID])->fetch_all(MYSQLI_ASSOC);

	foreach ($teams as $team) {
		array_push($returnArr, ...get_players_for_team($team["OPL_ID_team"]));
		sleep(1);
	}

	return $returnArr;
}

function get_players_for_team($teamID, bool $update_summoners = false):array {
	$returnArr = [];
	$dbcn = create_dbcn();
	$bearer_token = get_opl_bearer_token();
	$user_agent = get_user_agent_for_api_calls();

	if ($dbcn -> connect_error){
		return $returnArr;
	}

	$url = "https://www.opleague.pro/api/v4/team/$teamID/users";
	$options = ["http" => [
		"header" => [
			"Authorization: Bearer $bearer_token",
			"User-Agent: $user_agent",
		]
	]];
	$context = stream_context_create($options);
	$response = json_decode(file_get_contents($url, context: $context),true);

	$data = $response["data"]["users"];

	foreach ($data as $player) {
		$player_data = [
			"OPL_ID" => $player["ID"],
			"name" => $player["username"],
		];

		$updated = [];
		$written = false;

		$playerDB = $dbcn->execute_query("SELECT * FROM players WHERE OPL_ID = ?", [$player_data["OPL_ID"]])->fetch_assoc();

		if ($playerDB == NULL) {
			$written = true;
			$dbcn->execute_query("INSERT INTO players (OPL_ID, name) VALUES (?, ?)", [$player_data["OPL_ID"], $player_data["name"]]);
		} else {
			foreach ($player_data as $key=>$item) {
				if ($playerDB[$key] != $item) {
					$updated[$key] = ["old"=>$playerDB[$key], "new"=>$item];
				}
			}
			if (count($updated) > 0) {
				$dbcn->execute_query("UPDATE players SET name = ? WHERE OPL_ID = ?", [$player_data["name"], $player_data["OPL_ID"]]);
			}
		}

		$player_in_team = $dbcn->execute_query("SELECT * FROM players_in_teams WHERE OPL_ID_team = ? AND OPL_ID_player = ?", [$teamID, $player_data["OPL_ID"]])->fetch_assoc();
		if ($player_in_team == NULL) {
			$updated["teamID"] = ["old"=>NULL, "new"=>$teamID];
			$dbcn->execute_query("INSERT INTO players_in_teams (OPL_ID_player, OPL_ID_team) VALUES (?, ?)", [$player_data["OPL_ID"], $teamID]);
		}

		$returnArr[] = [
			"player" => $player_data,
			"written" => $written,
			"updated" => $updated,
		];
	}

	return $returnArr;
}

function get_summonerNames_for_player($playerID):array {
	$dbcn = create_dbcn();
	$bearer_token = get_opl_bearer_token();
	$user_agent = get_user_agent_for_api_calls();

	if ($dbcn -> connect_error){
		return [];
	}

	$playerDB = $dbcn->execute_query("SELECT * FROM players WHERE OPL_ID = ?", [$playerID])->fetch_assoc();

	if ($playerDB == NULL) {
		return [];
	}

	$url = "https://www.opleague.pro/api/v4/user/$playerID/launcher";
	$options = ["http" => [
		"header" => [
			"Authorization: Bearer $bearer_token",
			"User-Agent: $user_agent",
		]
	]];
	$context = stream_context_create($options);
	$response = json_decode(file_get_contents($url, context: $context),true);

	$data = $response["data"]["launcher"];

	if (!array_key_exists("13", $data)) {
		return ["updated"=>false, "old"=>$playerDB["summonerName"], "new"=>NULL];
	}

	$summonerName = $data["13"]["last_known_username"];

	if ($playerDB["summonerName"] == $summonerName) {
		return ["updated"=>false, "old"=>$playerDB["summonerName"], "new"=>$summonerName];
	}

	$dbcn->execute_query("UPDATE players SET summonerName = ? WHERE OPL_ID = ?", [$summonerName, $playerID]);

	$dbcn->close();
	return ["updated"=>true, "old"=>$playerDB["summonerName"], "new"=>$summonerName];
}

function get_matchups_for_tournament($tournamentID):array {
	$returnArr = [];
	$dbcn = create_dbcn();
	$bearer_token = get_opl_bearer_token();
	$user_agent = get_user_agent_for_api_calls();

	if ($dbcn -> connect_error){
		return $returnArr;
	}

	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournamentID])->fetch_assoc();

	if ($tournament["eventType"] != "group") {
		return [];
	}

	$url = "https://www.opleague.pro/api/v4/tournament/$tournamentID/matches";
	$options = ["http" => [
		"header" => [
			"Authorization: Bearer $bearer_token",
			"User-Agent: $user_agent",
		]
	]];
	$context = stream_context_create($options);
	$response = json_decode(file_get_contents($url, context: $context),true);

	$data = $response["data"]["matches"];

	foreach ($data as $match) {
		$match_data = [
			"OPL_ID" => $match["ID"],
			"OPL_ID_tournament" => $tournamentID,
			"OPL_ID_team1" => array_keys($match["teams"])[0],
			"OPL_ID_team2" => array_keys($match["teams"])[1],
			"plannedDate" => $match["to_be_played_on"]["date"],
			"playday" => $match["playday"],
			"bestOf" => $match["best_of"],
		];

		$updated = [];
		$written = false;

		$matchDB = $dbcn->execute_query("SELECT * FROM matchups WHERE OPL_ID = ?", [$match_data["OPL_ID"]])->fetch_assoc();

		if ($matchDB == NULL) {
			$written = true;
			$dbcn->execute_query("INSERT INTO matchups (OPL_ID, OPL_ID_tournament, OPL_ID_team1, OPL_ID_team2, plannedDate, playday, bestOf, played)
										VALUES (?, ?, ?, ?, ?, ?, ?, false)", [$match_data["OPL_ID"], $match_data["OPL_ID_tournament"], $match_data["OPL_ID_team1"], $match_data["OPL_ID_team2"], $match_data["plannedDate"], $match_data["playday"], $match_data["bestOf"]]);
		} else {
			foreach ($match_data as $key=>$item) {
				if ($matchDB[$key] != $item) {
					$updated[$key] = ["old"=>$matchDB[$key], "new"=>$item];
				}
			}
			if (count($updated) > 0) {
				$dbcn->execute_query("UPDATE matchups SET OPL_ID_tournament=?, OPL_ID_team1=?, OPL_ID_team2=?, plannedDate=?, playday=?, bestOf=? WHERE OPL_ID = ?", [$match_data["OPL_ID_tournament"], $match_data["OPL_ID_team1"], $match_data["OPL_ID_team2"], $match_data["plannedDate"], $match_data["playday"], $match_data["bestOf"], $match_data["OPL_ID"]]);
			}
		}

		$returnArr[] = [
			"match" => $match_data,
			"written" => $written,
			"updated" => $updated,
		];
	}

	$dbcn->close();
	return $returnArr;
}

function get_results_for_matchup($matchID):array {
	$dbcn = create_dbcn();
	$bearer_token = get_opl_bearer_token();
	$user_agent = get_user_agent_for_api_calls();

	if ($dbcn -> connect_error){
		return [];
	}

	$matchDB = $dbcn->execute_query("SELECT * FROM matchups WHERE OPL_ID = ?", [$matchID])->fetch_assoc();

	if ($matchDB == NULL) {
		return [];
	}

	$url = "https://www.opleague.pro/api/v4/matchup/$matchID/result";
	$options = ["http" => [
		"header" => [
			"Authorization: Bearer $bearer_token",
			"User-Agent: $user_agent",
		]
	]];
	$context = stream_context_create($options);
	$response = json_decode(file_get_contents($url, context: $context),true);

	$data = $response["data"]["result"];

	$match_data = [
		"team1Score" => strval($data["scores"][$matchDB["OPL_ID_team1"]]) ?? null,
		"team2Score" => strval($data["scores"][$matchDB["OPL_ID_team2"]]) ?? null,
		"played" => intval($response["data"]["state_key"]) >= 6,
		"winner" => (count($data["win_IDs"])>0) ? $data["win_IDs"][0] : null,
		"loser" => (count($data["win_IDs"])>0) ? $data["loss_IDs"][0] : null,
		"draw" => (intval($response["data"]["state_key"]) >= 6) ? count($data["draw_IDs"]) > 0 : null,
		"def_win" => (intval($response["data"]["state_key"]) >= 6) ? count($data["defwin"]) > 0 : null,
	];

	$updated = [];

	foreach ($match_data as $key=>$item) {
		if ($matchDB[$key] != $item) {
			$updated[$key] = ["old"=>$matchDB[$key], "new"=>$item];
		}
	}
	if (count($updated) > 0) {
		$dbcn->execute_query("UPDATE matchups SET team1Score = ?, team2Score = ?, played = ?, winner = ?, loser = ?, draw = ?, def_win = ? WHERE OPL_ID = ?", [$match_data["team1Score"], $match_data["team2Score"], $match_data["played"], $match_data["winner"], $match_data["loser"], $match_data["draw"], $match_data["def_win"], $matchID]);
	}

	$dbcn->close();
	return $updated;
}

function calculate_standings_from_matchups($tournamentID):array {
	$dbcn = create_dbcn();
	if ($dbcn -> connect_error){
		return [];
	}

	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournamentID])->fetch_assoc();

	if ($tournament["eventType"] != "group") {
		return [];
	}

	$teams = $dbcn->execute_query("SELECT * FROM teams JOIN teams_in_tournaments tit on teams.OPL_ID = tit.OPL_ID_team WHERE tit.OPL_ID_tournament = ?", [$tournamentID])->fetch_all(MYSQLI_ASSOC);

	$teams_standings = [];

	foreach ($teams as $team) {
		$standing = [
			"standing" => null,
			"played" => 0,
			"wins" => 0,
			"draws" => 0,
			"losses" => 0,
			"points" => 0,
		];
		$matches = $dbcn->execute_query("SELECT * FROM matchups WHERE OPL_ID_tournament = ? AND (OPL_ID_team1 = ? OR OPL_ID_team2 = ?)", [$tournamentID, $team["OPL_ID"], $team["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
		foreach ($matches as $match) {
			if ($match["played"]) {
				$standing["played"]++;
				if ($match["winner"] == $team["OPL_ID"]) {
					$standing["wins"]++;
				}
				if ($match["draw"]) {
					$standing["draws"]++;
				}
				if ($match["loser"] == $team["OPL_ID"]) {
					$standing["losses"]++;
				}
				if ($match["OPL_ID_team1"] == $team["OPL_ID"]) {
					$standing["points"] += $match["team1Score"];
				}
				if ($match["OPL_ID_team2"] == $team["OPL_ID"]) {
					$standing["points"] += $match["team2Score"];
				}
			}
		}
		$teams_standings[$team["OPL_ID"]] = $standing;
	}


	uasort($teams_standings, function ($a,$b) {
		if ($a["points"] == $b["points"]) {
			if ($a["wins"] == $b["wins"]) {
				if ($a["losses"] == $b["losses"]) {
					return 0;
				}
				return ($a["losses"] < $b["losses"]) ? -1 : 1;
			}
			return ($a["wins"] > $b["wins"]) ? -1 : 1;
		};
		return ($a["points"] > $b["points"]) ? -1 : 1;
	});

	$standing_counter = 1;
	$prev_ID = null;
	$prev_standing = null;
	foreach ($teams_standings as $teamID=>$team) {
		if ($team["played"] == 0) {
			continue;
		}
		if ($standing_counter == 1) {
			$teams_standings[$teamID]["standing"] = 1;
			$prev_standing = 1;
			$standing_counter++;
			$prev_ID = $teamID;
			continue;
		}
		if ($team["points"] == $teams_standings[$prev_ID]["points"] && $team["wins"] == $teams_standings[$prev_ID]["wins"] && $team["losses"] == $teams_standings[$prev_ID]["losses"]) {
			$teams_standings[$teamID]["standing"] = $prev_standing;
			$standing_counter++;
			$prev_ID = $teamID;
			continue;
		}
		$teams_standings[$teamID]["standing"] = $standing_counter;
		$prev_standing = $standing_counter;
		$standing_counter++;
		$prev_ID = $teamID;
	}

	$updated = [];
	foreach ($teams as $team) {
		$team_updates = [];

		foreach ($teams_standings[$team["OPL_ID"]] as $key=>$item) {
			if ($team[$key] !== $item) {
				$team_updates[$key] = ["old"=>$team[$key], "new"=>$item];
			}
		}

		if (count($team_updates) > 0) {
			$updated[$team["OPL_ID"]] = $team_updates;
		}
	}


	foreach ($teams_standings as $teamID=>$team) {
		$dbcn->execute_query("UPDATE teams_in_tournaments SET standing = ?, played = ?, wins = ?, draws = ?, losses = ?, points = ? WHERE OPL_ID_team = ? AND OPL_ID_tournament = ?", [$team["standing"], $team["played"], $team["wins"], $team["draws"], $team["losses"], $team["points"], $teamID, $tournamentID]);
	}

	return $updated;
}