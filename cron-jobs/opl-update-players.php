<?php
set_time_limit(600);
$day = date("d_m_y");
ini_set("log_errors", 1);
ini_set("error_log", "cron_logs/cron_errors/opl_players_$day.log");
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

echo "\n---- getting Players from OPL \n";
file_put_contents("cron_logs/cron_log_$day.log","\n----- Players starting -----\n".date("d.m.y H:i:s")." : Players for $tournament_id\n", FILE_APPEND);

$results = [];
$leagues = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'league'", [$tournament_id])->fetch_all(MYSQLI_ASSOC);
foreach ($leagues as $league) {
	file_put_contents("cron_logs/cron_log_$day.log",date("d.m.y H:i:s")." : Players for league {$league["number"]} ({$league["OPL_ID"]})\n", FILE_APPEND);
	$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'group'", [$league["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
	foreach ($groups as $group) {
		file_put_contents("cron_logs/cron_log_$day.log",date("d.m.y H:i:s")." : Players for group {$group["number"]} ({$group["OPL_ID"]})\n", FILE_APPEND);
		array_push($results, ...get_players_for_tournament($group["OPL_ID"]));
	}
}


$writes = $updates = 0;
foreach ($results as $result) {
	if ($result["written"]) $writes++;
	if (count($result["updated"])) $updates++;
}

file_put_contents("cron_logs/cron_log_$day.log","\n$writes Players written\n$updates Players updated\n"."----- Players done -----\n", FILE_APPEND);

echo "-------- $writes Players written\n";
echo "-------- $updates Players updated\n";
$dbcn->close();