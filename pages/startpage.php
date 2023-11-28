<?php
include_once __DIR__."/../setup/data.php";
include_once __DIR__."/../functions/fe-functions.php";

$pass = check_login();
?>
<!DOCTYPE html>
<html lang="de">
<?php
$lightmode = is_light_mode(true);

try {
	$dbcn = create_dbcn();
} catch (Exception $e) {
	echo create_html_head_elements(title: "Error");
	echo "<body class='$lightmode'>";
	echo create_header(title: "error", home_button: false);
	echo "<div style='text-align: center'>Database Connection failed</div></body>";
	exit();
}

echo create_html_head_elements();

?>
<body class="home <?php echo $lightmode?>">
<?php

$pass_wrong = $pass ? "" : "Falsches Passwort";
echo create_header($dbcn, home_button: FALSE, open_login: !$pass, loginforminfo: $pass_wrong);

?>
<div class="home-content">
	<div id="turnier-select">

		<h2>Spieler:</h2>
		<a href='spieler' class="button player-button"><?php echo "<div class='material-symbol'>" . file_get_contents(dirname(__FILE__) . "/../icons/material/person.svg") . "</div>" ?>Spielersuche</a>
		<h2>Turniere:</h2>

		<?php
		$local_img_path = "img/tournament_logos";
		$logo_filename = is_light_mode() ? "logo_light.webp" : "logo.webp";
		$tournaments = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType = 'tournament' AND deactivated = FALSE ORDER BY dateStart DESC")->fetch_all(MYSQLI_ASSOC);
		foreach ($tournaments as $tournament) {
			if ($tournament["OPL_ID_logo"] == NULL) {
				$tournimg_url = "";
			} else {
				$tournimg_url = $local_img_path."/". $tournament['OPL_ID_logo']."/".$logo_filename;
			}

			$t_name_clean = preg_replace("/LoL/","",$tournament["name"]);

			echo "
				<a href='turnier/{$tournament["OPL_ID"]}' class=\"turnier-button {$tournament["OPL_ID"]}\">
					<img class='color-switch' alt src='$tournimg_url'>
					<span>$t_name_clean</span>
				</a>";
		}
		$dbcn->close();
		?>
	</div>
</div>

</body>
</html>