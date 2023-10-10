<?php
include_once __DIR__.'/../setup/data.php';
$dbcn = create_dbcn();

if ($dbcn->connect_error) {
	echo "Database Connection failed";
	exit;
}
if (!(isset($_GET['t']))) {
	exit;
}
$tournament_id = $_GET['t'];

$time = date('Y-m-d H:i:s');
$lastupdate = $dbcn->execute_query("SELECT last_update FROM updates_cron WHERE OPL_ID_tournament = ?", [$tournament_id])->fetch_column();
if ($lastupdate == NULL) {
	$dbcn->execute_query("INSERT INTO updates_cron VALUES (?, ?)", [$tournament_id, $time]);
} else {
	$dbcn->execute_query("UPDATE updates_cron SET last_update = ? WHERE OPL_ID_tournament = ?", [$time,$tournament_id]);
}
