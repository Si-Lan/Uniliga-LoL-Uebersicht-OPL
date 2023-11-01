<?php
set_time_limit(600);
$day = date("d_m_y");
ini_set("log_errors", 1);
ini_set("error_log", "cron_logs/cron_errors/opl_standings_$day.log");
include_once __DIR__."/../admin/functions/get-opl-data.php";
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

echo "\n---- updating Standings\n";
file_put_contents("cron_logs/cron_log_$day.log","\n----- Standings starting -----\n".date("d.m.y H:i:s")." : Standings for $tournament_id\n", FILE_APPEND);

$results = [];
$leagues = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'league'", [$tournament_id])->fetch_all(MYSQLI_ASSOC);
foreach ($leagues as $league) {
	file_put_contents("cron_logs/cron_log_$day.log",date("d.m.y H:i:s")." : Standings for league {$league["number"]} ({$league["OPL_ID"]})\n", FILE_APPEND);
	$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'group'", [$league["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
	foreach ($groups as $group) {
		file_put_contents("cron_logs/cron_log_$day.log",date("d.m.y H:i:s")." : Standings for group {$group["number"]} ({$group["OPL_ID"]})\n", FILE_APPEND);
		$results[] = calculate_standings_from_matchups($group["OPL_ID"]);
	}
}


$updates = 0;
foreach ($results as $result) {
	if (count($result) > 0) $updates++;
}

file_put_contents("cron_logs/cron_log_$day.log","\n$updates Standings updated\n"."----- Standings done -----\n", FILE_APPEND);

echo "-------- $updates Standings updated\n";
$dbcn->close();