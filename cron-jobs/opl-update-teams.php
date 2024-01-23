<?php
$day = date("d_m_y");
ini_set("log_errors", 1);
ini_set("error_log", "cron_logs/cron_errors/opl_teams_$day.log");
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

echo "\n---- getting Teams from OPL\n";
file_put_contents("cron_logs/cron_log_$day.log","\n----- Teams starting -----\n".date("d.m.y H:i:s")." : Teams for $tournament_id\n", FILE_APPEND);

$results = [];
$leagues = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'league'", [$tournament_id])->fetch_all(MYSQLI_ASSOC);
foreach ($leagues as $league) {
	file_put_contents("cron_logs/cron_log_$day.log",date("d.m.y H:i:s")." : Teams for league {$league["number"]} ({$league["OPL_ID"]})\n", FILE_APPEND);
	$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'group'", [$league["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
	foreach ($groups as $group) {
		file_put_contents("cron_logs/cron_log_$day.log",date("d.m.y H:i:s")." : Teams for group {$group["number"]} ({$group["OPL_ID"]})\n", FILE_APPEND);
		array_push($results, ...get_teams_for_tournament($group["OPL_ID"]));
		sleep(1);
	}
}
$playoffs = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'playoffs'", [$tournament_id])->fetch_all(MYSQLI_ASSOC);
foreach ($playoffs as $playoff) {
	file_put_contents("cron_logs/cron_log_$day.log",date("d.m.y H:i:s")." : Teams for playoffs-group {$playoff["number"]}/{$playoff["numberRangeTo"]} ({$playoff["OPL_ID"]})\n", FILE_APPEND);
	array_push($results, ...get_teams_for_tournament($playoff["OPL_ID"]));
	sleep(1);
}

$writes = $updates = $dl = 0;
foreach ($results as $result) {
	if ($result["written"]) $writes++;
	if (count($result["updated"])) $updates++;
	if ($result["logo_downloaded"]) $dl++;
}

file_put_contents("cron_logs/cron_log_$day.log","$writes Teams written\n$updates Teams updated\n$dl Team-Logos downloaded\n"."----- Teams done -----\n", FILE_APPEND);

echo "-------- $writes Teams written\n";
echo "-------- $updates Teams updated\n";
echo "-------- $dl Team-Logos downloaded\n>";
$dbcn->close();