<?php
$day = date("d_m_y");
ini_set("log_errors", 1);
ini_set("error_log", "cron_logs/cron_errors/opl_matchresults_$day.log");
include_once dirname(__DIR__,2)."/src/admin/functions/get-opl-data.php";
include_once dirname(__DIR__,2).'/config/data.php';
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
		if ($league["format"] == "swiss") {
			$groups = [$league];
		} else {
			$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'group'", [$league["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
		}
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
$game_updates = 0;
foreach ($results as $result) {
	if (count($result["results"]) > 0) $updates++;
	foreach ($result["games"] as $game) {
		if ($game["newgame"] || $game["updated_gtm"]) {
			$game_updates++;
			break;
		}
	}
}

file_put_contents("cron_logs/cron_log_$day.log","$updates Matchresults updated\n"."----- Matchresults done -----\n", FILE_APPEND);

echo "-------- $updates Matchresults updated\n";
echo "-------- $game_updates Matches had Games updated\n";
$dbcn->close();