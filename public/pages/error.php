<?php
include_once dirname(__DIR__,2)."/src/functions/fe-functions.php";

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


$errortype = $_GET["error"] ?? NULL;

switch($errortype) {
    case "404":
		echo create_header(title: "404");
		echo "<div style='text-align: center'>Die gesuchte Seite wurde nicht gefunden</div>";
		break;
	default:
		echo create_header(title: "error");
		echo "<div style='text-align: center'>Ein unbekannter Fehler ist aufgetreten</div>";
}
?>
</body>
</html>