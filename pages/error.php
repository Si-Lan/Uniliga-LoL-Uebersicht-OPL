<?php
include_once __DIR__."/../functions/fe-functions.php";

$pass = check_login();
?>
<!DOCTYPE html>
<html lang="de">
<?php
$lightmode = is_light_mode(true);

echo create_html_head_elements();

?>
<body class="error <?php echo $lightmode?>">
<?php

$pass_wrong = $pass ? "" : "Falsches Passwort";

$errortype = $_GET["error"] ?? NULL;

switch($errortype) {
    case "404":
		echo create_header(title: "404", open_login: !$pass, loginforminfo: $pass_wrong);
		echo "<div style='text-align: center'>Die gesuchte Seite wurde nicht gefunden</div>";
		break;
	default:
		echo create_header(title: "error", open_login: !$pass, loginforminfo: $pass_wrong);
		echo "<div style='text-align: center'>Ein unbekannter Fehler ist aufgetreten</div>";
}
?>
</body>
</html>