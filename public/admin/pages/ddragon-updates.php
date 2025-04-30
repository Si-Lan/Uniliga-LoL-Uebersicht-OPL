<?php
$root = __DIR__."/../../";
include_once $root . "/config/data.php";
include_once $root."/functions/fe-functions.php";
include_once $root."/admin/functions/ddragon-update.php";

check_login();
?>
<!DOCTYPE html>
<html lang="de">
<?php

$dbcn = create_dbcn();
$loggedin = is_logged_in();
$lightmode = is_light_mode(true);

try {
	$dbcn = create_dbcn();
} catch (Exception $e) {
	echo create_html_head_elements(title: "Error");
	echo "<body class='$lightmode'>";
	echo create_header(title: "error");
	echo "<div style='text-align: center'>Database Connection failed</div></body>";
	exit();
}

echo create_html_head_elements(css: [""], js: ["admin"], title: "DDragon Updates | Uniliga LoL - Übersicht" ,loggedin: $loggedin);

?>
<body class="admin <?php echo $lightmode?>">
<?php

echo create_header($dbcn, title: "admin_dd", open_login: !$loggedin);

if ($loggedin) {

	$patches = $dbcn->execute_query("SELECT * FROM local_patches")->fetch_all(MYSQLI_ASSOC);
	usort($patches, function ($a,$b) {
		return version_compare($b["patch"],$a["patch"]);
	});

	?>
	<div class="patch-display">
		<dialog class='patch-result-popup dismissable-popup'>
			<div class='dialog-content'>

			</div>
		</dialog>
		<div class="patch-table">
			<div class="patch-header">
				<button type="button" class="open_add_patch_popup"><span>Patch hinzufügen</span></button>
				<button type="button" class="sync_patches"><span>Patches synchronisieren</span></button>
				<dialog class="add-patch-popup dismissable-popup">
					<div class="dialog-content">
						<?php
						echo create_dropdown("get-patches",["new"=>"neue Patches","missing"=>"fehlende Patches","old"=>"alte Patches"]);
						?>
						<div class='popup-loading-indicator' style="display: none"></div>
						<div class='add-patches-display'>
							<?php
							echo create_add_patch_view($dbcn, "new");
							?>
						</div>
					</div>
				</dialog>
			</div>
			<div class="get-patch-options">
				<input type="checkbox" id="force-overwrite-patch-img" name="force-overwrite-patch-img">
				<label for="force-overwrite-patch-img">Alle Bilder herunterladen und überschreiben erzwingen</label>
			</div>
			<?php
			echo generate_patch_rows($dbcn);
			?>
		</div>
	</div>
	<?php
}
?>
</body>
</html>

