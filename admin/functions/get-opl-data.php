<?php
$root = __DIR__."/../../";
include_once $root."setup/data.php";

function get_tournament($id):array {
	$returnArr = ["return"=>1, "echo"=>""];
	$dbcn = create_dbcn();
	$bearer_token = get_opl_bearer_token();
	$user_agent = get_user_agent_for_api_calls();

	if ($dbcn -> connect_error){
		$returnArr["echo"] .= "<span style='color: red'>Database Connection failed : " . $dbcn->connect_error . "<br></span>";
		return $returnArr;
	}

	$url = "https://www.opleague.pro/api/v4/tournament/$id";
	$options = ["http" => [
		"header" => [
			"Authorization: Bearer $bearer_token",
			"User-Agent: $user_agent",
			]
		]
	];
	$context = stream_context_create($options);
	$response = json_decode(file_get_contents($url, false, $context),true);

	$data = $response["data"];

	$name = $data["name"];
	$dateStart = $data["start_on"]["date"] ?? NULL;
	$dateEnd = $data["end_on"]["date"] ?? NULL;
	$logo_url = $data["logo_array"]["background"] ?? NULL;
	$logo_id = ($logo_url != NULL) ? explode("/", $logo_url, -1) : NULL;
	$logo_id = ($logo_id != NULL) ? end($logo_id) : NULL;

	if (str_contains($name,"Sommer")){
		$split = "Sommer";
		$pos = strpos($name, "Sommer");
		$season = substr($name, $pos+7, 2);
	} elseif (str_contains($name,"Winter")){
		$split = "Winter";
		$pos = strpos($name, "Winter");
		$season = substr($name, $pos+7, 2);
	} else {
		$returnArr["echo"] .= "<span style='color: orangered'>Keine Sommer/Winterseason gefunden <br></span>";
		$split = NULL;
		$season = NULL;
	}

	$type = NULL;
	if (str_contains($name, "Wildcard")) {
		$type = "wildcard";
	} elseif (str_contains($name, "Gruppe")) {
		$type = "group";
	} elseif (str_contains($name, "Liga")) {
		$type = "league";
	}

	// regex prüft ob name mit einer zahl oder zwei zahlen mit - oder / getrennt endet
	$number_matches = [];
	$number = $numberTo = NULL;
	if (preg_match("#[0-9]([-/][0-9])?$#",$name,$number_matches)) {
		$number = $number_matches[0];
	} else {
		$returnArr["echo"] .= "<span style='color: orangered'>Keine Nummer gefunden <br></span>";
	}

	if (strlen($number) > 1) {
		$numberTo = substr($number,2,1);
		$number = substr($number,0,1);
	}


	//TODO: über split/season mit DB table top_tournaments abgleichen


	$returnArr["echo"] .= "
			name: $name <br>
			Split: $split <br>
			Season: $season <br>
			Typ: $type <br>
			Num: $number <br>
			NumRangeTo: $numberTo <br>
			von: $dateStart <br>
			bis: $dateEnd <br>
			logo: <a href='https://www.opleague.pro/$logo_url'>$logo_id</a>";

	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$id])->fetch_assoc();

	//TODO: prüfen, ob daten geändert wurden und updaten
	if ($tournament == NULL) {
		$returnArr["echo"] .= "<span style='color: lawngreen'>- schreibe in DB<br></span>";
		$dbcn->execute_query("INSERT INTO
			tournaments (OPL_ID, name, split, season, eventType, number, numberRangeTo, dateStart, dateEnd, OPL_logo_url, OPL_ID_logo)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [$id, $name, $split, $season, $type, $number, $numberTo, $dateStart, $dateEnd, $logo_url, $logo_id]);
	}

	$local_img_folder_path = __DIR__."/../../img/tournament_logos";
	if ($logo_url != NULL && !file_exists("$local_img_folder_path/$logo_id/logo.webp")) {
		if (!is_dir("$local_img_folder_path/$logo_id")) {
			mkdir("$local_img_folder_path/$logo_id");
		}
		$logo_url_trim = ltrim($logo_url,".");
		$img_response = imagecreatefromstring(file_get_contents("https://www.opleague.pro$logo_url_trim", false, $context));

		$img = $img_response;
		if ($img) {
			imagepalettetotruecolor($img);
			imagealphablending($img, false);
			imagesavealpha($img, true);
			imagewebp($img, "$local_img_folder_path/$logo_id/logo.webp", 100);
			imagedestroy($img);
			$returnArr["echo"] .= "--Logo heruntergeladen<br>";
		}
	}

	return $returnArr;
}