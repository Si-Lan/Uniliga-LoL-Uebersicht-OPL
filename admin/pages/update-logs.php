<!DOCTYPE html>
<html lang="de">
<?php
$root = __DIR__."/../../";
include_once $root."/setup/data.php";
include_once $root."/functions/fe-functions.php";

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

echo create_html_head_elements(css: [""], js: ["admin"], title: "Update Log | Uniliga LoL - Übersicht" ,loggedin: $loggedin);

?>
<body class="admin <?php echo $lightmode?>">
<?php

echo create_header($dbcn, title: "admin_update_log", open_login: !$loggedin);

if ($loggedin) {
    echo "<h2 style='text-align: start'>Logs:</h2>";
	$dir = new DirectoryIterator(__DIR__."/../../cron-jobs/cron_logs");
	foreach ($dir as $file) {
		if ($file->isDot() || $file->isDir() || $file->getFilename() == ".gitignore" || $file->getFilename() == ".htaccess") {
			continue;
		}
		echo "<a href='cron-jobs/cron_logs/".$file->getFilename()."'>".$file->getFilename()."</a><br>";
	}
	echo "<h2 style='text-align: start'>Errors:</h2>";
	$dir = new DirectoryIterator(__DIR__."/../../cron-jobs/cron_logs/cron_errors");
	foreach ($dir as $file) {
		if ($file->isDot() || $file->isDir() || $file->getFilename() == ".gitignore") {
			continue;
		}
		echo "<a href='cron-jobs/cron_logs/cron_errors/".$file->getFilename()."'>".$file->getFilename()."</a><br>";
	}
	?>

	<?php
}
?>
</body>
</html>

