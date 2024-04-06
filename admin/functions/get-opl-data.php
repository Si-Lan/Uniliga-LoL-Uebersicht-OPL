<?php
$root = __DIR__."/../../";
include_once $root."setup/data.php";
include_once $root."admin/functions/img-download.php";

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
	$response = json_decode(@file_get_contents($url, false, $context),true);
	if (str_contains($http_response_header[0], "404")) {
		$returnArr["response"] = "404";
		return $returnArr;
	}

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

	if (preg_match("/(?:winter|sommer) *[0-9]*([0-9]{2})/",$name_lower,$season_match)) {
		$season = $season_match[1];
	}

	$type = NULL;
	if (str_contains($name_lower, "wildcard")) {
		$type = "wildcard";
	} elseif (preg_match("/playoffs?/",$name_lower)) {
		$type = "playoffs";
	} elseif (str_contains($name_lower, "gruppe ") || str_contains($name_lower, "group ")) {
		$type = "group";
	} elseif (str_contains($name_lower, " liga")) {
		$type = "league";
	} else {
		$type = "tournament";
	}

	// regex prüft ob name mit einer zahl oder zwei zahlen mit - oder / getrennt endet
	$number_matches = [];
	$number = $numberTo = NULL;
	if (preg_match("#([0-9])\.?([-/]([0-9])\.?)?(\s?Liga)?$#",$name,$number_matches) && $type != "tournament") {
		$returnArr["dump"] = $number_matches;
		$number = $number_matches[1];
		if (array_key_exists(3,$number_matches)) {
			$numberTo = $number_matches[3];
		}
	} else {
		$returnArr["info"] .= "<span style='color: orangered'>Keine Nummer gefunden <br></span>";
	}
	if ($number == NULL && $type == "group" && preg_match("#[A-Z]$#",$name, $number_matches)) {
		$number = $number_matches[0];
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
	if ($data["OPL_logo_url"] != NULL && (!file_exists("$local_img_folder_path/{$data["OPL_ID_logo"]}/logo.webp") || !file_exists("$local_img_folder_path/{$data["OPL_ID_logo"]}/logo_light.webp"))) {
		download_opl_img($data["OPL_ID"], "tournament_logo", true);
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

	$formatselect_round_robin = (strtolower($data["format"]??"") == "round-robin") ? "selected" : "";
	$formatselect_single_elim = (strtolower($data["format"]??"") == "single-elimination") ? "selected" : "";
	$formatselect_double_elim = (strtolower($data["format"]??"") == "double-elimination") ? "selected" : "";

	$dateStart = explode(" ",$data["dateStart"])[0];
	$dateEnd = explode(" ",$data["dateEnd"])[0];

	$deactivated_check = ($data["deactivated"]) ? "" : "checked";
	$finished_check = ($data["finished"]) ? "checked" : "";

	$result .= "
				<div class='write_tournament_row wtrow-1'>
					<label class=\"write_tournament_id\"><input type=\"text\" value=\"{$data["OPL_ID"]}\" readonly></label>
					<label class=\"write_tournament_name\"><input type=\"text\" value=\"{$data["name"]}\" readonly></label>
					<label class=\"write_tournament_type\">
						<span class=\"slct\">
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
					<label class=\"write_tournament_parent\">Parent:<input type=\"text\" value=\"{$data["OPL_ID_parent"]}\"></label>
				</div>
				<div class='write_tournament_row wtrow-2'>
					<label class=\"write_tournament_split\">
						<span class=\"slct\">
							<select>
								<option disabled hidden value=''>nicht erkannt</option>
								<option $splitselect_winter value='winter'>Winter</option>
								<option $splitselect_sommer value='sommer'>Sommer</option>
							</select>
							<span class='material-symbol'>".file_get_contents(__DIR__."/../../icons/material/arrow_drop_down.svg") ."</span>
						</span>
					</label>
					<label class=\"write_tournament_season\"><input type=\"number\" value=\"{$data["season"]}\" placeholder='##'></label>
					<label class=\"write_tournament_number\">Nummer:<input type=\"text\" value=\"{$data["number"]}\" placeholder='#'></label>
					<label class=\"write_tournament_number2\"><input type=\"text\" value=\"{$data["numberRangeTo"]}\" placeholder='#'></label>
					<label class=\"write_tournament_startdate\">Zeitraum<input type=\"date\" value=\"{$dateStart}\"></label>
					<label class=\"write_tournament_enddate\"><input type=\"date\" value=\"{$dateEnd}\"></label>
				</div>
				<div class='write_tournament_row wtrow-3'>
					<label class=\"write_tournament_format\">
						<span class=\"slct\">
							<select>
								<option value=''>Format wählen</option>
								<option $formatselect_round_robin value='round-robin'>round-robin</option>
								<option $formatselect_single_elim value='single-elimination'>single-elim</option>
								<option $formatselect_double_elim value='double-elimination'>double-elim</option>
							</select>
							<span class='material-symbol'>".file_get_contents(__DIR__."/../../icons/material/arrow_drop_down.svg")."</span>
						</span>
					</label>
					<label class=\"write_tournament_show\">Anzeigen:<input type=\"checkbox\" $deactivated_check></label>
					<label class=\"write_tournament_finished\">Beendet:<input type=\"checkbox\" $finished_check></label>
					<label class=\"write_tournament_logoid\">Logo:<input type=\"number\" value=\"{$data["OPL_ID_logo"]}\" readonly></label>
					<label class=\"write_tournament_logourl\"><input type=\"text\" value=\"{$data["OPL_logo_url"]}\" readonly></label>
				</div>";

	$result .= "</div>";
	$result .= "<div class='tournament-write-button-wrapper'>";
	if ($in_write_popup) $result .= "<button class=\"write_tournament\" type=\"button\">Eintragen</button>";
	if (!$in_write_popup) $result .= "<button class=\"update_tournament\" type=\"button\" data-id='$id_class'>Aktualisieren</button>";
	if (!$in_write_popup) $result .= "<button class=\"get_event_children\" type=\"button\" data-id='$id_class'>Kinder holen</button>";
	if (!$in_write_popup) $result .= "<button class=\"get_event_parents\" type=\"button\" data-id='$id_class'>Eltern holen</button>";
	if (!$in_write_popup) $result .= "<button class=\"open-tournament-data-popup $id_class\" type=\"button\" data-id='$id_class'>weitere Daten holen</button>";
	$result .= "</div>";

	if (!$in_write_popup) $result .= "
			<dialog class='tournament-data-popup dismissable-popup $id_class'>
				<div class='dialog-content'>
					<h2>{$data["name"]} ({$data["eventType"]})</h2>
					<button class='get-teams $id_class' data-id='$id_class'><span>Teams im Turnier updaten</span></button>
					<button class='get-teams-delete $id_class' data-id='$id_class'><span>Teams im Turnier updaten (Alte entfernen)</span></button>
					<button class='get-players $id_class' data-id='$id_class'><span>Spieler im Turnier updaten</span></button>
					<button class='get-riotids $id_class' data-id='$id_class'><span>Spieler-Accounts im Turnier updaten</span></button>
					<button class='get-matchups $id_class' data-id='$id_class'><span>Matches im Turnier updaten</span></button>
					<button class='get-matchups-delete $id_class' data-id='$id_class'><span>Matches im Turnier updaten (Alte entfernen)</span></button>
					<button class='get-results $id_class' data-id='$id_class'><span>Match-Ergebnisse im Turnier updaten</span></button>
					<button class='calculate-standings $id_class' data-id='$id_class'><span>Tabelle des Turniers aktualisieren</span></button>
				</div>
			</dialog>";

	$result .= "</div>";
	return $result;
}

function get_related_events($tournamentID, string $relation = "children"):array {
	$dbcn = create_dbcn();
	$bearer_token = get_opl_bearer_token();
	$user_agent = get_user_agent_for_api_calls();

	$url = "https://www.opleague.pro/api/v4/tournament/$tournamentID";
	$options = ["http" => [
		"header" => [
			"Authorization: Bearer $bearer_token",
			"User-Agent: $user_agent",
		]
	]];
	$context = stream_context_create($options);
	$response = json_decode(file_get_contents($url, false, $context),true);

	if ($relation == "children") {
		$related_events = $response["data"]["leafes"];
	} elseif ($relation == "parents") {
		$related_events = $response["data"]["ancestors"];
	} else {
		return [];
	}
	$tournaments = $dbcn->execute_query("SELECT OPL_ID FROM tournaments")->fetch_all();
	$tournaments = array_merge(...$tournaments);
	$related_events_dbcheck = [];
	foreach ($related_events as $event) {
		$related_events_dbcheck[] = [$event, in_array($event,$tournaments)];
	}

	return $related_events_dbcheck;
}

function get_teams_for_tournament($tournamentID, bool $deletemissing = false):array {
	$returnArr = [];
	$dbcn = create_dbcn();
	$bearer_token = get_opl_bearer_token();
	$user_agent = get_user_agent_for_api_calls();

	if ($dbcn -> connect_error){
		return $returnArr;
	}

	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournamentID])->fetch_assoc();
	if (!($tournament["eventType"] == "group" || $tournament["eventType"] == "playoffs")) {
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

	$team_ids = [];

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
		$team_ids[] = $team["ID"];

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
		$team_in_tournament = $dbcn->execute_query("SELECT * FROM teams_in_tournaments WHERE OPL_ID_team = ? AND OPL_ID_group = ?", [$team_data["OPL_ID"], $tournamentID])->fetch_assoc();
		if ($team_in_tournament == NULL) {
			$updated["tournamentID"] = ["old"=>NULL, "new"=>$tournamentID];
			$dbcn->execute_query("INSERT INTO teams_in_tournaments (OPL_ID_team, OPL_ID_group) VALUES (?, ?)", [$team_data["OPL_ID"], $tournamentID]);
		}

		// Team Logo herunterladen, wenn das aktuelle logo älter als eine woche ist
		$last_update = strtotime($dbcn->execute_query("SELECT last_logo_download FROM teams WHERE OPL_ID = ?", [$team_data["OPL_ID"]])->fetch_column() ?? "");
		$current_time = strtotime(date('Y-m-d H:i:s'));
		if ($team_data["OPL_logo_url"] != NULL && $current_time-$last_update > 604800) {
			$logo_dl = download_opl_img($team_data["OPL_ID"], "team_logo");
		}

		$returnArr[] = [
			"team" => $team_data,
			"written" => $written,
			"updated" => $updated,
			"logo_downloaded" => $logo_dl,
		];
	}

	if ($deletemissing) {
		$teams_in_tournament = $dbcn->execute_query("SELECT * FROM teams_in_tournaments WHERE OPL_ID_group = ?", [$tournamentID])->fetch_all(MYSQLI_ASSOC);
		foreach ($teams_in_tournament as $team) {
			if (!in_array($team["OPL_ID_team"],$team_ids)) {
				$dbcn->execute_query("DELETE FROM teams_in_tournaments WHERE OPL_ID_team = ? AND OPL_ID_group = ?", [$team["OPL_ID_team"],$tournamentID]);
				$returnArr[] = [
					"team" => [
						"OPL_ID" => $team["OPL_ID_team"],
						"OPL_ID_logo" => NULL,
						"OPL_logo_url" => NULL,
						"name" => NULL,
						"shortName" => NULL,
					],
					"written" => false,
					"updated" => [],
					"logo_downloaded" => false,
					"deleted" => true,
				];
			}
		}
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

	if (!($tournament["eventType"] == "group" || $tournament["eventType"] == "playoffs")) {
		return [];
	}

	$teams = $dbcn->execute_query("SELECT * FROM teams_in_tournaments WHERE OPL_ID_group = ?", [$tournamentID])->fetch_all(MYSQLI_ASSOC);

	foreach ($teams as $team) {
		array_push($returnArr, ...get_players_for_team($team["OPL_ID_team"],$tournamentID));
		sleep(1);
	}

	return $returnArr;
}

function get_players_for_team($teamID, $tournamentID):array {
	$returnArr = [];
	if ($teamID == -1) return $returnArr;
	$dbcn = create_dbcn();
	$bearer_token = get_opl_bearer_token();
	$user_agent = get_user_agent_for_api_calls();

	if ($dbcn -> connect_error){
		return $returnArr;
	}

	$tournament_data = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournamentID])->fetch_assoc();
	$current_time = time();
	$parent_tournament = $dbcn->execute_query("SELECT *
														FROM tournaments
														WHERE (
														    eventType='tournament'
														        AND OPL_ID = (
														        	SELECT OPL_ID_parent
														        	FROM tournaments
														        	WHERE eventType= 'league'
														          		AND OPL_ID = (
														          		    SELECT OPL_ID_parent
														          		    FROM tournaments
														          		    WHERE eventType='group'
														          		    	AND OPL_ID = ?
														          		)
														        	)
														    )
														   OR (
														    eventType='tournament'
														        AND OPL_ID = (
														        	SELECT OPL_ID_parent
														        	FROM tournaments
														        	WHERE eventType= 'league'
														          		AND OPL_ID = ?
														        	)
														       )
														   OR (
														    eventType='tournament'
														        AND OPL_ID = (
														        	SELECT OPL_ID_parent
														        	FROM tournaments
														        	WHERE eventType= 'playoffs'
														          		AND OPL_ID = ?
														        	)
														       )
														    OR (
														    eventType='tournament'
														        AND OPL_ID = ?
														       )",
		[$tournamentID, $tournamentID, $tournamentID, $tournamentID])->fetch_assoc();
	$parent_tournamentID = $parent_tournament["OPL_ID"];
	$tournament_end = strtotime($parent_tournament["dateEnd"]);

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

	$player_IDs = [];

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
		} else {
			$dbcn->execute_query("UPDATE players_in_teams SET removed = FALSE WHERE OPL_ID_player = ? AND OPL_ID_team = ?", [$player_data["OPL_ID"], $teamID]);
		}

		if ($current_time - $tournament_end < 0) {
			$player_in_team_in_tournament = $dbcn->execute_query("SELECT * FROM players_in_teams_in_tournament WHERE OPL_ID_team = ? AND OPL_ID_player = ? AND OPL_ID_tournament = ?", [$teamID, $player_data["OPL_ID"], $parent_tournamentID])->fetch_assoc();
			if ($player_in_team_in_tournament == NULL) {
				$updated["teamID_tournament"] = ["old"=>NULL, "new"=>$teamID];
				$dbcn->execute_query("INSERT INTO players_in_teams_in_tournament (OPL_ID_player, OPL_ID_team, OPL_ID_tournament) VALUES (?, ?, ?)", [$player_data["OPL_ID"], $teamID, $parent_tournamentID]);
			} else {
				$dbcn->execute_query("UPDATE players_in_teams_in_tournament SET removed = FALSE WHERE OPL_ID_player = ? AND OPL_ID_team = ? AND OPL_ID_tournament = ?", [$player_data["OPL_ID"], $teamID, $parent_tournamentID]);
			}
		}

		$player_IDs[] = $player_data["OPL_ID"];

		$returnArr[] = [
			"player" => $player_data,
			"written" => $written,
			"updated" => $updated,
		];
	}

	$players_t = $dbcn->execute_query("SELECT * FROM players_in_teams WHERE OPL_ID_team = ?", [$teamID])->fetch_all(MYSQLI_ASSOC);
	$players_tt = $dbcn->execute_query("SELECT * FROM players_in_teams_in_tournament WHERE OPL_ID_team = ? AND OPL_ID_tournament = ?", [$teamID, $parent_tournamentID])->fetch_all(MYSQLI_ASSOC);

	if (count($player_IDs) > 0) {
		foreach ($players_t as $player) {
			if (!in_array($player["OPL_ID_player"], $player_IDs)) {
				$dbcn->execute_query("UPDATE players_in_teams SET removed = TRUE WHERE OPL_ID_player = ? AND OPL_ID_team = ?", [$player["OPL_ID_player"], $teamID]);
			}
		}
		if ($current_time - $tournament_end < 0) {
			foreach ($players_tt as $player) {
				if (!in_array($player["OPL_ID_player"], $player_IDs)) {
					$dbcn->execute_query("UPDATE players_in_teams_in_tournament SET removed = TRUE WHERE OPL_ID_player = ? AND OPL_ID_team = ? AND OPL_ID_tournament = ?", [$player["OPL_ID_player"], $teamID, $parent_tournamentID]);
				}
			}
		}
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
function get_riotid_for_player($playerID):array {
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

	// benutze übergangsweise launcher-id 6 (valorant) bis für alle accounts riot-id für LoL (launcher-id 13) aktualisiert wurde
	if (!array_key_exists("6", $data)) {
		return ["updated"=>false, "old"=>[$playerDB["riotID_name"], $playerDB["riotID_tag"]], "new"=>NULL];
	}

	$riotID = explode("#",$data["6"]["last_known_username"]);
	$riotID_name = $riotID[0];
	$riotID_tag = $riotID[1];

	if ($playerDB["riotID_name"] == $riotID_name && $playerDB["riotID_tag"] == $riotID_tag) {
		return ["updated"=>false, "old"=>[$playerDB["riotID_name"], $playerDB["riotID_tag"]], "new"=>[$riotID_name, $riotID_tag]];
	}

	$dbcn->execute_query("UPDATE players SET riotID_name = ?, riotID_tag = ? WHERE OPL_ID = ?", [$riotID_name, $riotID_tag, $playerID]);

	$dbcn->close();
	return ["updated"=>true, "old"=>[$playerDB["riotID_name"], $playerDB["riotID_tag"]], "new"=>[$riotID_name, $riotID_tag]];
}

function get_matchups_for_tournament($tournamentID, bool $deletemissing = false):array {
	$returnArr = [];
	$dbcn = create_dbcn();
	$bearer_token = get_opl_bearer_token();
	$user_agent = get_user_agent_for_api_calls();

	if ($dbcn -> connect_error){
		return $returnArr;
	}

	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournamentID])->fetch_assoc();

	if (!($tournament["eventType"] == "group" || $tournament["eventType"] == "playoffs")) {
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

	$match_ids = [];

	foreach ($data as $match) {
		$teamids = array_keys($match["teams"]);
		$t1id = ($teamids[0] == "") ? null : $teamids[0];
		$t2id = (count($teamids) < 2 || $teamids[1] == "") ? null : $teamids[1];
		$match_data = [
			"OPL_ID" => $match["ID"],
			"OPL_ID_tournament" => $tournamentID,
			"OPL_ID_team1" => $t1id,
			"OPL_ID_team2" => $t2id,
			"plannedDate" => $match["to_be_played_on"]["date"] ?? null,
			"playday" => $match["playday"],
			"bestOf" => $match["best_of"],
		];
		$match_ids[] = $match["ID"];

		$updated = [];
		$written = false;

		$matchDB = $dbcn->execute_query("SELECT * FROM matchups WHERE OPL_ID = ?", [$match_data["OPL_ID"]])->fetch_assoc();

		if ($matchDB == NULL) {
			$written = true;
			$dbcn->execute_query("INSERT INTO matchups (OPL_ID, OPL_ID_tournament, OPL_ID_team1, OPL_ID_team2, plannedDate, playday, bestOf, played)
										VALUES (?, ?, ?, ?, ?, ?, ?, false)", [$match_data["OPL_ID"], $match_data["OPL_ID_tournament"], $match_data["OPL_ID_team1"], $match_data["OPL_ID_team2"], $match_data["plannedDate"], $match_data["playday"], $match_data["bestOf"]]);
		} else {
			foreach ($match_data as $key=>$item) {
				if ($key == "plannedDate" && $matchDB[$key] != null && $item != null) {
					if (strtotime($matchDB[$key]) != strtotime($item)) {
						$updated[$key] = ["old"=>$matchDB[$key], "new"=>$item];
					}
					continue;
				}
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

	if ($deletemissing) {
		$matchups = $dbcn->execute_query("SELECT * FROM matchups WHERE OPL_ID_tournament = ?", [$tournamentID])->fetch_all(MYSQLI_ASSOC);
		foreach ($matchups as $match) {
			if (!in_array($match["OPL_ID"],$match_ids)) {
				$dbcn->execute_query("DELETE FROM matchups WHERE OPL_ID = ?", [$match["OPL_ID"]]);
				$returnArr[] = [
					"match" => $match,
					"written" => false,
					"updated" => [],
					"deleted" => true,
				];
			}
		}
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
	if(str_contains($http_response_header[0],"429")) {
		sleep(1);
		$response = json_decode(file_get_contents($url, context: $context),true);
		if (str_contains($http_response_header[0],"429")) trigger_error("Custom Warning: second try after 429 failed", E_USER_NOTICE);
	}

	$data = $response["data"]["result"];

	$match_data = [
		"team1Score" => strval($data["scores"][$matchDB["OPL_ID_team1"]]) ?? null,
		"team2Score" => strval($data["scores"][$matchDB["OPL_ID_team2"]]) ?? null,
		"played" => intval($response["data"]["state_key"]) >= 4,
		"winner" => (count($data["win_IDs"]??[])>0) ? $data["win_IDs"][0] : null,
		"loser" => (count($data["win_IDs"]??[])>0) ? $data["loss_IDs"][0] : null,
		"draw" => (intval($response["data"]["state_key"]) >= 4) ? count($data["draw_IDs"]) > 0 : null,
		"def_win" => (intval($response["data"]["state_key"]) >= 4) ? count($data["defwin"]) > 0 : null,
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

	$teams = $dbcn->execute_query("SELECT * FROM teams JOIN teams_in_tournaments tit on teams.OPL_ID = tit.OPL_ID_team WHERE tit.OPL_ID_group = ? AND teams.OPL_ID <> -1", [$tournamentID])->fetch_all(MYSQLI_ASSOC);

	$teams_standings = [];

	foreach ($teams as $team) {
		$standing = [
			"id" => $team["OPL_ID"],
			"standing" => null,
			"played" => 0,
			"wins" => 0,
			"draws" => 0,
			"losses" => 0,
			"points" => 0,
			"wins_vs" => [],
		];
		$matches = $dbcn->execute_query("SELECT * FROM matchups WHERE OPL_ID_tournament = ? AND (OPL_ID_team1 = ? OR OPL_ID_team2 = ?)", [$tournamentID, $team["OPL_ID"], $team["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
		foreach ($matches as $match) {
			$enemy_id = ($team["OPL_ID"] == $match["OPL_ID_team1"]) ? $match["OPL_ID_team2"] : $match["OPL_ID_team1"];
			if (!array_key_exists($enemy_id,$standing["wins_vs"])) $standing["wins_vs"][$enemy_id] = 0;
			if ($match["played"]) {
				$standing["played"]++;
				if ($match["winner"] == $team["OPL_ID"]) {
					$standing["wins"]++;
					$standing["wins_vs"][$enemy_id] += ($enemy_id == $match["OPL_ID_team1"]) ? intval($match["team2Score"]) : intval($match["team1Score"]);
				}
				if ($match["draw"]) {
					$standing["draws"]++;
				}
				if ($match["loser"] == $team["OPL_ID"]) {
					$standing["losses"]++;
				}
				if ($match["OPL_ID_team1"] == $team["OPL_ID"]) {
					if (is_numeric($match["team1Score"])) {
						$standing["points"] += $match["team1Score"];
					} else {
						$standing["points"] += ($match["team1Score"] == "W") ? $match["bestOf"] : 0;
					}
				}
				if ($match["OPL_ID_team2"] == $team["OPL_ID"]) {
					if (is_numeric($match["team2Score"])) {
						$standing["points"] += $match["team2Score"];
					} else {
						$standing["points"] += ($match["team2Score"] == "W") ? $match["bestOf"] : 0;
					}
				}
			}
		}
		$teams_standings[$team["OPL_ID"]] = $standing;
	}


	uasort($teams_standings, function ($a,$b) {
		if ($a["points"] != $b["points"]) return ($a["points"] > $b["points"]) ? -1 : 1;
		if (array_key_exists($b["id"],$a["wins_vs"]) && $a["wins_vs"][$b["id"]] != $b["wins_vs"][$a["id"]]) return ($a["wins_vs"][$b["id"]] > $b["wins_vs"][$a["id"]]) ? -1 : 1;
		if ($a["wins"] != $b["wins"]) return ($a["wins"] > $b["wins"]) ? -1 : 1;
		if ($a["losses"] != $b["losses"]) return ($a["losses"] < $b["losses"]) ? -1 : 1;
		return 0;
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
		unset($teams_standings[$team["OPL_ID"]]["id"]);
		unset($teams_standings[$team["OPL_ID"]]["wins_vs"]);
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
		$dbcn->execute_query("UPDATE teams_in_tournaments SET standing = ?, played = ?, wins = ?, draws = ?, losses = ?, points = ? WHERE OPL_ID_team = ? AND OPL_ID_group = ?", [$team["standing"], $team["played"], $team["wins"], $team["draws"], $team["losses"], $team["points"], $teamID, $tournamentID]);
	}

	return $updated;
}