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
		$split = "Sommer";
	} elseif (str_contains($name_lower,"Winter")){
		$split = "Winter";
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




	//TODO: über split/season mit anderen tournaments abgleichen
	$parent = NULL;

	$returnArr["data"] = [
		"OPL_ID" => $id,
		"OPL_ID_parent" => $parent,
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
		$logo_url_trim = ltrim($data["OPL_ID_logo"],".");

		$user_agent = get_user_agent_for_api_calls();
		$options = ["http" => [
			"header" => [
				"User-Agent: $user_agent",
			]
		]];
		$context = stream_context_create($options);
		$img_response = imagecreatefromstring(file_get_contents("https://www.opleague.pro$logo_url_trim", false, $context));

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
	$result .= "</div>";
	return $result;
}