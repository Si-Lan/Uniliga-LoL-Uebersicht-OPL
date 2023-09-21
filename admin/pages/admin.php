<!DOCTYPE html>
<html lang="de">
<?php
$root = __DIR__."/../../";
include_once $root."functions/fe-functions.php";
include_once $root."functions/helper.php";
include_once $root."setup/data.php";
include_once $root."admin/functions/fe-functions.php";

$dbcn = create_dbcn();
$loggedin = is_logged_in();
$lightmode = is_light_mode(true);

echo create_html_head_elements(css: ["admin"], js: ["admin"], loggedin: $loggedin);

?>
<body class="<?php echo $lightmode?>">
<?php

echo create_header($dbcn, title: "admin", open_login: !$loggedin);

if ($loggedin) {
	?>
	<h1>OPL -> Database</h1>

	<div id="main-selection">
		<label for="input-tournament-id"></label><input id="input-tournament-id" name="id" placeholder="Tournament ID" type="number">
		<button id="turnier-button-get">Turnier hinzufügen</button>
		<div class="turnier-get-result no-res get-result"></div>
	</div>

	<h2>Turniere in Datenbank:</h2>
	<div class="turnier-select">
		<?php
		echo create_tournament_buttons($dbcn);
		?>
	</div>
	<?php
}
?>
</body>
</html>
