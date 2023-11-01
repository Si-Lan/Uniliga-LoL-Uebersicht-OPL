<?php
set_time_limit(600);
$day = date("d_m_y");
ini_set("log_errors", 1);
ini_set("error_log", "cron_logs/cron_errors/opl_matchresults_$day.log");
include_once __DIR__."/../admin/functions/get-opl-data.php";
include_once __DIR__.'/../setup/data.php';
$dbcn = create_dbcn();

if ($dbcn->connect_error) {
	echo "Database Connection failed";
	exit;
}
if (!(isset($_GET['t'])) && !isset($_GET['g'])) {
	exit;
}
$tournament_id = $_GET['t'] ?? NULL;
$group_id = $_GET['g'] ?? NULL;

echo "\n---- getting Matchresults from OPL \n";
$results = [];

if ($group_id != NULL) {
	file_put_contents("cron_logs/cron_log_$day.log","\n----- Matchresults starting -----\n".date("d.m.y H:i:s")." : Matchresults for group $group_id\n", FILE_APPEND);
	$matches = $dbcn->execute_query("SELECT * FROM matchups WHERE OPL_ID_tournament = ?", [$group_id])->fetch_all(MYSQLI_ASSOC);
	foreach ($matches as $match) {
		$results[] = get_results_for_matchup($match["OPL_ID"]);
		sleep(1);
	}
} elseif ($tournament_id != NULL) {
	file_put_contents("cron_logs/cron_log_$day.log","\n----- Matchresults starting -----\n".date("d.m.y H:i:s")." : Matchresults for tournament $tournament_id\n", FILE_APPEND);
	$leagues = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'league'", [$tournament_id])->fetch_all(MYSQLI_ASSOC);
	foreach ($leagues as $league) {
		file_put_contents("cron_logs/cron_log_$day.log",date("d.m.y H:i:s")." : Matchresults for league {$league["number"]} ({$league["OPL_ID"]})\n", FILE_APPEND);
		$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'group'", [$league["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
		foreach ($groups as $group) {
			file_put_contents("cron_logs/cron_log_$day.log",date("d.m.y H:i:s")." : Matchresults for group {$group["number"]} ({$group["OPL_ID"]})\n", FILE_APPEND);
			$matches = $dbcn->execute_query("SELECT * FROM matchups WHERE OPL_ID_tournament = ?", [$group["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
			foreach ($matches as $match) {
				$results[] = get_results_for_matchup($match["OPL_ID"]);
				sleep(1);
			}
		}
	}
}


$updates = 0;
foreach ($results as $result) {
	if (count($result) > 0) $updates++;
}

file_put_contents("cron_logs/cron_log_$day.log","\n$updates Matchresults updated\n"."----- Matchresults done -----\n", FILE_APPEND);

echo "-------- $updates Matchresults updated\n";
$dbcn->close();