<?php
include_once __DIR__."/../setup/data.php";
include_once __DIR__."/../functions/fe-functions.php";

$pass = check_login();
?>
<!DOCTYPE html>
<html lang="de">
<?php
$lightmode = is_light_mode(true);
$logged_in = is_logged_in();
$admin_btns = admin_buttons_visible(true);

try {
	$dbcn = create_dbcn();
} catch (Exception $e) {
	echo create_html_head_elements(title: "Error");
	echo "<body class='$lightmode'>";
	echo create_header(title: "error");
	echo "<div style='text-align: center'>Database Connection failed</div></body>";
	exit();
}

$playerID = $_GET["player"] ?? NULL;

$player = $dbcn->execute_query("SELECT * FROM players WHERE OPL_ID = ?", [$playerID])->fetch_assoc();

if ($player == NULL) {
	echo create_html_head_elements(title: "Spieler nicht gefunden | Uniliga LoL - Übersicht");
	echo "<body class='$lightmode'>";
	echo create_header(title: "error");
	echo "<div style='text-align: center'>Kein Spieler unter der angegebenen ID gefunden!</div></body>";
	exit();
}

echo create_html_head_elements(title: "{$player["name"]} | Uniliga LoL - Übersicht", loggedin: $logged_in);

?>
<body class="team <?php echo "$lightmode $admin_btns"?>">
<?php

$pass_wrong = $pass ? "" : "Falsches Passwort";
echo create_header(dbcn: $dbcn, title: "player", open_login: !$pass, loginforminfo: $pass_wrong);

?>
</body>
</html>