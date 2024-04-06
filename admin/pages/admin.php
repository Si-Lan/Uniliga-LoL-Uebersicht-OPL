<?php
$root = __DIR__."/../../";
include_once $root."functions/fe-functions.php";
include_once $root."functions/helper.php";
include_once $root."setup/data.php";
include_once $root."admin/functions/fe-functions.php";

$pass = check_login();
?>
<!DOCTYPE html>
<html lang="de">
<?php

$dbcn = create_dbcn();
$loggedin = is_logged_in();
$lightmode = is_light_mode(true);

echo create_html_head_elements(css: [""], js: ["admin"], title: "Admin-Panel | Uniliga LoL - Übersicht" ,loggedin: $loggedin);

?>
<body class="admin <?php echo $lightmode?>">
<?php

echo create_header($dbcn, title: "admin", open_login: !$loggedin);

$maintenance_mode = (file_exists(__DIR__."/../../setup/maintenance.enable")) ? "on" : "off";

if ($loggedin) {
	?>
	<h1>OPL -> Database</h1>

	<div class="main-content">
		<dialog class='write-result-popup dismissable-popup'>
			<div class='dialog-content'></div>
		</dialog>
        <button type="button" id="maintenance-mode" class="maintenance-<?php echo $maintenance_mode ?>">Maintenance Mode</button>
		<div id="main-selection">
			<span class="searchbar"> <label for="input-tournament-id"></label><input id="input-tournament-id" name="id" placeholder="Tournament ID" type="number"> </span>
			<button id="turnier-button-get">Turnier hinzufügen</button>
		</div>
		<dialog id="tournament-add" class="">
			<div class="dialog-content">
				<button class="close-popup"><span class="material-symbol"><?php echo file_get_contents(__DIR__."/../../icons/material/close.svg") ?></span></button>
				<div class="close-button-space"></div>

			</div>
		</dialog>
        <dialog id="related-add" class="clear-on-exit">
            <div class="dialog-content">
                <button class="close-popup"><span class="material-symbol"><?php echo file_get_contents(__DIR__."/../../icons/material/close.svg") ?></span></button>
                <div class="close-button-space"></div>
            </div>
        </dialog>

		<h2>Turniere in Datenbank:</h2>
		<div class="turnier-select">
			<?php
			echo create_tournament_buttons($dbcn);
			?>
		</div>
	</div>
	<?php
}
?>
</body>
</html>
