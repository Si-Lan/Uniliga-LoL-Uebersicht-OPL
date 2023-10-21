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

$teamID = $_GET["team"] ?? NULL;

$team = $dbcn->execute_query("SELECT * FROM teams WHERE OPL_ID = ?", [$teamID])->fetch_assoc();

if ($team == NULL) {
	echo create_html_head_elements(title: "Turnier nicht gefunden | Uniliga LoL - Übersicht");
	echo "<body class='$lightmode'>";
	echo show_old_url_warning($teamID);
	echo create_header(title: "error");
	echo "<div style='text-align: center'>Kein Team unter der angegebenen ID gefunden!</div></body>";
	exit();
}

echo create_html_head_elements(title: "{$team["name"]} | Uniliga LoL - Übersicht", loggedin: $logged_in);

?>
<body class="team <?php echo "$lightmode $admin_btns"?>">
<?php

$pass_wrong = $pass ? "" : "Falsches Passwort";
echo create_header(dbcn: $dbcn, title: "team", open_login: !$pass, loginforminfo: $pass_wrong);

?>
</body>
</html>
