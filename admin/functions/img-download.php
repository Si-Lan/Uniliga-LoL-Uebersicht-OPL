<?php
include_once __DIR__."/../../setup/data.php";
function download_opl_img(int|string $itemID, string $type, bool $echo_states = false):bool {
	$dbcn = create_dbcn();
	$user_agent = get_user_agent_for_api_calls();
	$opl_logo_url = $opl_logo_url_light = NULL;
	if ($type == "team_logo") {
		$imgfolder_path = __DIR__."/../../img/team_logos";
		$item = $dbcn->execute_query("SELECT name, OPL_ID_logo FROM teams WHERE OPL_ID = ?", [$itemID])->fetch_assoc();
		$opl_logo_url_light = "/styles/media/team/{$item["OPL_ID_logo"]}/Logo_100.webp";
		$opl_logo_url = "/styles/media/team/{$item["OPL_ID_logo"]}/Logo_on_black_100.webp";
	} elseif ($type == "tournament_logo") {
		$imgfolder_path = __DIR__."/../../img/tournament_logos";
		$item = $dbcn->execute_query("SELECT name, OPL_ID_logo FROM tournaments WHERE OPL_ID = ?", [$itemID])->fetch_assoc();
		$opl_logo_url_light = "/styles/media/event/{$item["OPL_ID_logo"]}/Logo_100.webp";
        $opl_logo_url = "/styles/media/event/{$item["OPL_ID_logo"]}/Logo_on_black_100.webp";
	} else {
		$dbcn->close();
		return false;
	}

	if ($item["OPL_ID_logo"] == NULL) return false;

	if (!is_dir("$imgfolder_path/{$item["OPL_ID_logo"]}")) {
		if ($echo_states) echo "Logo-Directory erstellt<br>";
		mkdir("$imgfolder_path/{$item["OPL_ID_logo"]}");
	}

	$local_tournament_directory_path = "$imgfolder_path/{$item["OPL_ID_logo"]}";

	$img_written = false;

	$options = ["http" => [
		"header" => [
			"User-Agent: $user_agent",
		]
	]];
	$context = stream_context_create($options);

	$imgdata_dark = @file_get_contents("https://www.opleague.pro$opl_logo_url", context: $context);
	if (str_contains($http_response_header[0], "200")) {
		$img_dark = imagecreatefromstring($imgdata_dark);
		if ($img_dark) {
			imagepalettetotruecolor($img_dark);
			imagealphablending($img_dark, false);
			imagesavealpha($img_dark, true);
			imagewebp($img_dark, "$local_tournament_directory_path/logo.webp", 100);
			if ($echo_states) echo "--Logo heruntergeladen<br>";
			$img_written = true;
		}
	}

	$imgdata_light = @file_get_contents("https://www.opleague.pro$opl_logo_url_light", context: $context);
	if (str_contains($http_response_header[0], "200")) {
		$img_light = imagecreatefromstring($imgdata_light);
		if ($img_light) {
			imagepalettetotruecolor($img_light);
			imagealphablending($img_light, false);
			imagesavealpha($img_light, true);
			imagewebp($img_light, "$local_tournament_directory_path/logo_light.webp", 100);
			if ($echo_states) echo "--Logo für lightmode heruntergeladen<br>";
			$img_written = true;
		}
		if (!($img_dark??false)) {
			imagewebp($img_light, "$local_tournament_directory_path/logo.webp", 100);
			if ($echo_states) echo "--Logo light für logo dark eingesetzt<br>";
		}
	} else {
		if ($img_dark??false) {
			imagewebp($img_dark, "$local_tournament_directory_path/logo_light.webp", 100);
			if ($echo_states) echo "--Logo dark für logo light eingesetzt<br>";
		}
	}

	if ($img_dark ?? false) imagedestroy($img_dark);
	if ($img_light ?? false) imagedestroy($img_light);

	$dbcn->close();
	return $img_written;
}