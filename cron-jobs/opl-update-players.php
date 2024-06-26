<?php
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
if (!(isset($_GET['t'])) && !isset($_GET['g'])) {
	exit;
}
$tournament_id = $_GET['t'] ?? null;
$group_id = $_GET['g'] ?? null;

echo "\n---- getting Players from OPL \n";
file_put_contents("cron_logs/cron_log_$day.log","\n----- Players starting -----\n", FILE_APPEND);

$results = [];
if ($group_id != null) {
	$group = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$group_id])->fetch_assoc();
	if ($group["eventType"] == "group") {
		$league = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = (SELECT OPL_ID_parent FROM tournaments WHERE OPL_ID = ? AND eventType = 'group') AND eventType = 'league'", [$group_id])->fetch_assoc();
		file_put_contents("cron_logs/cron_log_$day.log",date("d.m.y H:i:s")." : Players for league {$league["number"]} group {$group["number"]} ({$league["OPL_ID"]} / {$group["OPL_ID"]})\n", FILE_APPEND);
	} elseif ($group["eventType"] == "playoffs") {
		file_put_contents("cron_logs/cron_log_$day.log",date("d.m.y H:i:s")." : Players for playoffs {$group["number"]}/{$group["numberRangeTo"]} ({$group["OPL_ID"]})\n", FILE_APPEND);
	} elseif ($group["eventType"] == "league" && $group["format"] == "swiss") {
		file_put_contents("cron_logs/cron_log_$day.log",date("d.m.y H:i:s")." : Players for swiss-league {$group["number"]} ({$group["OPL_ID"]})\n", FILE_APPEND);
	} else {
		file_put_contents("cron_logs/cron_log_$day.log",date("d.m.y H:i:s")." : Players not gotten, id $group_id is neither group, swiss-league nor playoffs\n", FILE_APPEND);
	}
	array_push($results, ...get_players_for_tournament($group["OPL_ID"]));
} elseif ($tournament_id != null) {
	file_put_contents("cron_logs/cron_log_$day.log",date("d.m.y H:i:s")." : Players for tournament $tournament_id:\n", FILE_APPEND);
	$leagues = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'league'", [$tournament_id])->fetch_all(MYSQLI_ASSOC);
	foreach ($leagues as $league) {
		if ($league["format"] == "swiss") {
			file_put_contents("cron_logs/cron_log_$day.log",date("d.m.y H:i:s")." : Players for swiss-league {$league["number"]} ({$league["OPL_ID"]})\n", FILE_APPEND);
			array_push($results, ...get_players_for_tournament($league["OPL_ID"]));
			sleep(1);
			continue;
		}
		file_put_contents("cron_logs/cron_log_$day.log",date("d.m.y H:i:s")." : Players for league {$league["number"]} ({$league["OPL_ID"]})\n", FILE_APPEND);
		$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'group'", [$league["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
		foreach ($groups as $group) {
			file_put_contents("cron_logs/cron_log_$day.log",date("d.m.y H:i:s")." : Players for group {$group["number"]} ({$group["OPL_ID"]})\n", FILE_APPEND);
			array_push($results, ...get_players_for_tournament($group["OPL_ID"]));
		}
	}
	$playoffs = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'playoffs'", [$tournament_id])->fetch_all(MYSQLI_ASSOC);
	foreach ($playoffs as $playoff) {
		file_put_contents("cron_logs/cron_log_$day.log",date("d.m.y H:i:s")." : Players for playoffs-group {$playoff["number"]}/{$playoff["numberRangeTo"]} ({$playoff["OPL_ID"]})\n", FILE_APPEND);
		array_push($results, ...get_players_for_tournament($playoff["OPL_ID"]));
	}
}


$writes = $updates = 0;
foreach ($results as $result) {
	if ($result["written"]) $writes++;
	if (count($result["updated"])) $updates++;
}

file_put_contents("cron_logs/cron_log_$day.log","$writes Players written\n$updates Players updated\n"."----- Players done -----\n", FILE_APPEND);

echo "-------- $writes Players written\n";
echo "-------- $updates Players updated\n";
$dbcn->close();