<?php
include_once __DIR__."/../../setup/data.php";
function download_toornament_img(mysqli $dbcn, int|string $itemID, string $type, bool $echo_states = false):bool {
	$dbcn = create_dbcn();
	$user_agent = get_user_agent_for_api_calls();
	if ($type == "team_logo") {
		$imgfolder_path = __DIR__."/../../img/team_logos";
		$item = $dbcn->execute_query("SELECT name, OPL_ID_logo FROM teams WHERE OPL_ID = ?", [$itemID])->fetch_assoc();
		$opl_logo_url = "/styles/media/team/{$item["OPL_ID_logo"]}/Logo_100.webp";
	} elseif ($type == "tournament_logo") {
		$imgfolder_path = __DIR__."/../../img/tournament_logos";
		$item = $dbcn->execute_query("SELECT name, OPL_ID_logo FROM tournaments WHERE OPL_ID = ?", [$itemID])->fetch_assoc();
		$opl_logo_url = "/styles/media/event/{$item["OPL_ID_logo"]}/Logo_100.webp";
	} else {
		$dbcn->close();
		return false;
	}

	if (!is_dir("$imgfolder_path/{$item["OPL_ID_logo"]}")) {
		if ($echo_states) echo "directory doesn't exist, creating it<br>";
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
	$img = imagecreatefromstring(file_get_contents("https://www.opleague.pro$opl_logo_url"));
	if ($img) {
		imagepalettetotruecolor($img);
		imagealphablending($img, false);
		imagesavealpha($img, true);
		imagewebp($img, "$local_tournament_directory_path/logo.webp", 100);
		imagedestroy($img);
		if ($echo_states) echo "--saved Logo<br>";
		$img_written = true;
	}

	if ($echo_states) echo "----added img_local to DB entry<br>";

	$dbcn->close();
	return $img_written;
}