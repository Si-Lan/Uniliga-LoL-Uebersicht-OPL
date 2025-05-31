<?php
include_once dirname(__DIR__,3) . "/config/data.php";
function download_opl_img(int|string $itemID, string $type, bool $echo_states = false):bool {
	$dbcn = create_dbcn();
	$user_agent = get_user_agent_for_api_calls();
	$today = date("Y-m-d");
	$opl_logo_url = $opl_logo_url_light = NULL;
	if ($type == "team_logo") {
		$imgfolder_path = dirname(__DIR__, 3) . "/public/assets/img/team_logos";
		$item = $dbcn->execute_query("SELECT name, OPL_ID_logo FROM teams WHERE OPL_ID = ?", [$itemID])->fetch_assoc();
		$opl_logo_url_light = "/styles/media/team/{$item["OPL_ID_logo"]}/Logo_100.webp";
		$opl_logo_url = "/styles/media/team/{$item["OPL_ID_logo"]}/Logo_on_black_100.webp";
	} elseif ($type == "tournament_logo") {
		$imgfolder_path = dirname(__DIR__, 3) . "/public/assets/img/tournament_logos";
		$item = $dbcn->execute_query("SELECT name, OPL_ID_logo FROM tournaments WHERE OPL_ID = ?", [$itemID])->fetch_assoc();
		$opl_logo_url_light = "/styles/media/event/{$item["OPL_ID_logo"]}/Logo_100.webp";
        $opl_logo_url = "/styles/media/event/{$item["OPL_ID_logo"]}/Logo_on_black_100.webp";
	} else {
		$dbcn->close();
		return false;
	}

	if ($item["OPL_ID_logo"] == NULL) return false;

	$local_tournament_directory_path = "$imgfolder_path/{$item["OPL_ID_logo"]}";

	$first_logo = false;
	if (!is_dir($local_tournament_directory_path) || !file_exists("$local_tournament_directory_path/logo.webp")) {
		$first_logo = true;
	}

	if (!is_dir($local_tournament_directory_path)) {
		if ($echo_states) echo "Logo-Directory erstellt<br>";
		mkdir($local_tournament_directory_path);
	}

	$img_dark_written = $img_light_written = false;

	$options = ["http" => [
		"header" => [
			"User-Agent: $user_agent",
		]
	]];
	$context = stream_context_create($options);

	$imgdata_dark = @file_get_contents("https://www.opleague.pro$opl_logo_url", context: $context);
	$img_dark_exists = str_contains($http_response_header[0], "200");
	$imgdata_light = @file_get_contents("https://www.opleague.pro$opl_logo_url_light", context: $context);
	$img_light_exists = str_contains($http_response_header[0], "200");

	$new_logo = false;
	if (!$first_logo && $img_dark_exists) {
		$img_dark = imagecreatefromstring($imgdata_dark);
		$img_dark = square_logo($img_dark);

		$logo_comparision = is_new_logo($img_dark,"$local_tournament_directory_path/logo_square.webp");

		if ($type == "team_logo" && $logo_comparision[0]) {
			if ($echo_states) echo "--neues Logo<br>";
			$current_logo = $dbcn->execute_query("SELECT * FROM team_logo_history WHERE OPL_ID_team = ? AND dir_key = -1", [$itemID])->fetch_assoc();
			$latest_logo = $dbcn->execute_query("SELECT * FROM team_logo_history WHERE OPL_ID_team = ? ORDER BY dir_key DESC", [$itemID])->fetch_assoc();
			if ($current_logo != null) {
				$dir_key = $latest_logo["dir_key"]+1;
				copy_old_team_logos($itemID, $dir_key);
				$dbcn->execute_query("UPDATE team_logo_history SET dir_key = ? WHERE OPL_ID_team = ? AND dir_key = -1", [$dir_key,$itemID]);
			}
			$dbcn->execute_query("INSERT INTO team_logo_history (OPL_ID_team, dir_key, update_time, diff_to_prev) VALUES (?,?,?,?)",[$itemID,-1,$today,$logo_comparision[1]]);
			$new_logo = true;
		}

	}
	if (!$first_logo && $img_light_exists && !$new_logo) {
		$img_light = imagecreatefromstring($imgdata_light);
		$img_light = square_logo($img_light);

		$logo_comparision_l = is_new_logo($img_light,"$local_tournament_directory_path/logo_light_square.webp");

		if ($type == "team_logo" && $logo_comparision_l[0]) {
			if ($echo_states) echo "--neues Logo<br>";
			$current_logo = $dbcn->execute_query("SELECT * FROM team_logo_history WHERE OPL_ID_team = ? AND dir_key = -1", [$itemID])->fetch_assoc();
			$latest_logo = $dbcn->execute_query("SELECT * FROM team_logo_history WHERE OPL_ID_team = ? ORDER BY dir_key DESC", [$itemID])->fetch_assoc();
			if ($current_logo != null) {
				$dir_key = $latest_logo["dir_key"]+1;
				copy_old_team_logos($itemID, $dir_key);
				$dbcn->execute_query("UPDATE team_logo_history SET dir_key = ? WHERE OPL_ID_team = ? AND dir_key = -1", [$dir_key,$itemID]);
			}
			$dbcn->execute_query("INSERT INTO team_logo_history (OPL_ID_team, dir_key, update_time, diff_to_prev) VALUES (?,?,?,?)",[$itemID,-1,$today,$logo_comparision_l[1]]);
			$new_logo = true;
		}

	}


	if ($img_dark_exists) {
		$img_dark_square = square_logo(imagecreatefromstring($imgdata_dark));
		if (create_and_save_webp($imgdata_dark, "$local_tournament_directory_path/logo.webp", 100)) {
			save_webp($img_dark_square,"$local_tournament_directory_path/logo_square.webp", 100);
			if ($echo_states) echo "--Logo heruntergeladen<br>";
			$img_dark_written = true;
		}
		if (!$img_light_exists) {
			if (create_and_save_webp($imgdata_dark, "$local_tournament_directory_path/logo_light.webp", 100)) {
				save_webp($img_dark_square,"$local_tournament_directory_path/logo_light_square.webp", 100);
				if ($echo_states) echo "--Logo dark für logo light eingesetzt<br>";
			}
		}
	}

	if ($img_light_exists) {
		$img_light_square = square_logo(imagecreatefromstring($imgdata_light));
		if (create_and_save_webp($imgdata_light, "$local_tournament_directory_path/logo_light.webp", 100)) {
			save_webp($img_light_square,"$local_tournament_directory_path/logo_light_square.webp", 100);
			if ($echo_states) echo "--Logo für lightmode heruntergeladen<br>";
			$img_light_written = true;
		}
		if (!$img_dark_exists) {
			if (create_and_save_webp($imgdata_light, "$local_tournament_directory_path/logo.webp", 100)) {
				save_webp($img_light_square,"$local_tournament_directory_path/logo_square.webp", 100);
				if ($echo_states) echo "--Logo light für logo dark eingesetzt<br>";
			}
		}
	}


	if ($type == "team_logo" && ($img_dark_written || $img_light_written)) $dbcn->execute_query("UPDATE teams SET last_logo_download = ? WHERE OPL_ID = ?", [date('Y-m-d H:i:s'), $itemID]);
	if ($first_logo && ($img_dark_written || $img_light_written)) $dbcn->execute_query("INSERT INTO team_logo_history (OPL_ID_team, dir_key, update_time) VALUES (?,?,?)",[$itemID,-1,$today]);

	$dbcn->close();
	return ($img_dark_written || $img_light_written);
}

function create_and_save_webp($img_string, $target_file, $webp_quality):bool {
	$img = imagecreatefromstring($img_string);
	if ($img) {
		save_webp($img, $target_file, $webp_quality);
		imagedestroy($img);
		return true;
	} else {
		imagedestroy($img);
		return false;
	}
}
function save_webp($img, $target_file, $webp_quality) {
	imagepalettetotruecolor($img);
	imagealphablending($img, false);
	imagesavealpha($img, true);
	imagewebp($img, $target_file, $webp_quality);
}

function square_logo($img) {
	$img_size = max(imagesx($img),imagesy($img));
	$img_square = imagecreate($img_size,$img_size);
	imagepalettetotruecolor($img_square);
	imagealphablending($img_square, false);
	$transparency = imagecolorallocatealpha($img_square, 0,0,0,127);
	imagefill($img_square, 0, 0, $transparency);
	imagesavealpha($img_square, true);
	imagecopy($img_square, $img,intval(($img_size-imagesx($img))/2),intval(($img_size-imagesy($img))/2), 0, 0, imagesx($img), imagesy($img));
	return $img_square;
}

function copy_old_team_logos($id,$dir_key) {
	$img_dir = dirname(__DIR__, 3) . "/public/assets/img/team_logos";
	if (!is_dir("$img_dir/$id/$dir_key")) {
		mkdir("$img_dir/$id/$dir_key");
	}
	$dir = new DirectoryIterator("$img_dir/$id");
	foreach ($dir as $fileinfo) {
		if (!$fileinfo->isDot() && !$fileinfo->isDir() && $fileinfo->getExtension() == "webp") {
			$img = $fileinfo->getFilename();
			copy("$img_dir/$id/$img","$img_dir/$id/$dir_key/$img");
		}
	}
	$missing_files = false;
	foreach ($dir as $fileinfo) {
		if (!$fileinfo->isDot() && !$fileinfo->isDir() && $fileinfo->getExtension() == "webp") {
			$img = $fileinfo->getFilename();
			if (!file_exists("$img_dir/$id/$dir_key/$img")) {
				$missing_files = true;
			}
		}
	}

	return !$missing_files;
}

function is_new_logo($new_img, $old_img_location) {
	$comparision = @custom_compareImages($new_img, $old_img_location, 20);
	if ($comparision < 35) {
		return [true,$comparision];
	} else {
		return [false,$comparision];
	}
}

function custom_compareImages($imagePathA, $imagePathB, $accuracy):float|int {
	//load base image
	$bim = (gettype($imagePathA) == "string") ? imagecreatefromwebp($imagePathA) : $imagePathA;
	//create comparison points
	$bimX = imagesx($bim);
	$bimY = imagesy($bim);
	$pointsX = $accuracy*5;
	$pointsY = $accuracy*5;
	$sizeX = round($bimX/$pointsX);
	$sizeY = round($bimY/$pointsY);

	//load image into an object
	$im = (gettype($imagePathB) == "string") ? imagecreatefromwebp($imagePathB) : $imagePathB;

	$transparencyA = imagecolorallocatealpha($bim, 0,0,0,127);
	$transparencyB = imagecolorallocatealpha($im, 0,0,0,127);
	//loop through each point and compare the color of that point
	$y = 0;
	$matchcount = 0;
	$num = 0;
	for ($i=0; $i <= $pointsY; $i++) {
		$x = 0;
		for($n=0; $n <= $pointsX; $n++){

			$rgba = imagecolorat($bim, $x, $y);
			$colorsa = imagecolorsforindex($bim, $rgba);

			$rgbb = imagecolorat($im, $x, $y);
			$colorsb = imagecolorsforindex($im, $rgbb);

			if ($rgba == $transparencyA && $rgbb == $transparencyB) {
				$x += $sizeX;
				continue;
			}

			if(colorComp($colorsa['red'], $colorsb['red']) && colorComp($colorsa['green'], $colorsb['green']) && colorComp($colorsa['blue'], $colorsb['blue'])){
				//point matches
				$matchcount ++;
			}
			$x += $sizeX;
			$num++;
		}
		$y += $sizeY;
	}
	//take a rating of the similarity between the points, if over 90 percent they match.
	$rating = $matchcount*(100/$num);
	return $rating;
}
function colorComp($color, $c){
	//test to see if the point matches - within boundaries
	if($color >= $c-10 && $color <= $c+10){
		return true;
	}else{
		return false;
	}
}

function square_all_teamlogos() {
	$img_dir = dirname(__DIR__, 3) . "/public/assets/img/team_logos";
	$dir = new DirectoryIterator("$img_dir");
	foreach ($dir as $team_dir) {
		if (!$team_dir->isDir() || $team_dir->isDot()) {
			continue;
		}
		$team_path = $team_dir->getPathname();
		$subdir = new DirectoryIterator($team_path);
		foreach ($subdir as $file) {
			if ($file->isDot() || $file->isDir() || $file->getExtension() != "webp" || str_contains($file->getFilename(),"_square")) {
				continue;
			}
			$img_path = $file->getPathname();
			$img = imagecreatefromwebp($img_path);
			$img_name = $file->getBasename('.webp');
			$img_square = square_logo($img);
			save_webp($img_square, "$team_path/{$img_name}_square.webp",100);
		}
	}
}