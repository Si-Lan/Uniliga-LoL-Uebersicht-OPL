<?php
$day = date("d_m_y");
ini_set("log_errors", 1);
ini_set("error_log", "cron_logs/cron_errors/opl_riotids_$day.log");
include_once dirname(__DIR__,2)."/src/old_functions/admin/get-opl-data.php";
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

echo "\n---- getting RiotIDs from OPL \n";
$results = [];

if ($group_id != NULL) {
	file_put_contents("cron_logs/cron_log_$day.log","\n----- RiotIDs starting -----\n".date("d.m.y H:i:s")." : RiotIDs for group $group_id\n", FILE_APPEND);
	$players = $dbcn->execute_query("SELECT *
											FROM players
											    JOIN players_in_teams_in_tournament pit
											        ON players.OPL_ID = pit.OPL_ID_player
											               AND OPL_ID_tournament =
											                   (SELECT OPL_ID_top_parent
											                    FROM tournaments
											                    WHERE tournaments.OPL_ID = ?)
											    JOIN teams_in_tournaments tit
											        ON pit.OPL_ID_team = tit.OPL_ID_team
											WHERE OPL_ID_group = ?", [$group_id, $group_id])->fetch_all(MYSQLI_ASSOC);
	foreach ($players as $player) {
		$results[] = get_riotid_for_player($player["OPL_ID"]);
		sleep(1);
	}
} elseif ($tournament_id != NULL) {
	file_put_contents("cron_logs/cron_log_$day.log","\n----- RiotIDs starting -----\n".date("d.m.y H:i:s")." : RiotIDs for tournament $group_id\n", FILE_APPEND);
	$leagues = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'league'", [$tournament_id])->fetch_all(MYSQLI_ASSOC);
	foreach ($leagues as $league) {
		if ($league["format"] == "swiss") {
			file_put_contents("cron_logs/cron_log_$day.log",date("d.m.y H:i:s")." : RiotIDs for swiss-league {$league["number"]} ({$league["OPL_ID"]})\n", FILE_APPEND);
			$players = $dbcn->execute_query("SELECT *
													FROM players
													    JOIN players_in_teams_in_tournament pit
													        ON players.OPL_ID = pit.OPL_ID_player
													               AND OPL_ID_tournament =
													                   (SELECT OPL_ID_top_parent
													                    FROM tournaments
													                    WHERE tournaments.OPL_ID = ?)
													    JOIN teams_in_tournaments tit
													        ON pit.OPL_ID_team = tit.OPL_ID_team
													WHERE OPL_ID_group = ?", [$league["OPL_ID"],$league["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
			foreach ($players as $player) {
				$results[] = get_riotid_for_player($player["OPL_ID"]);
				sleep(1);
			}
			continue;
		}
		file_put_contents("cron_logs/cron_log_$day.log",date("d.m.y H:i:s")." : RiotIDs for league {$league["number"]} ({$league["OPL_ID"]})\n", FILE_APPEND);
		$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'group'", [$league["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
		foreach ($groups as $group) {
			file_put_contents("cron_logs/cron_log_$day.log",date("d.m.y H:i:s")." : RiotIDs for group {$group["number"]} ({$group["OPL_ID"]})\n", FILE_APPEND);
			$players = $dbcn->execute_query("SELECT *
													FROM players
													    JOIN players_in_teams_in_tournament pit
													        ON players.OPL_ID = pit.OPL_ID_player
													               AND OPL_ID_tournament =
													                   (SELECT OPL_ID_top_parent
													                    FROM tournaments
													                    WHERE tournaments.OPL_ID = ?)
													    JOIN teams_in_tournaments tit
													        ON pit.OPL_ID_team = tit.OPL_ID_team
													WHERE OPL_ID_group = ?", [$group["OPL_ID"],$group["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
			foreach ($players as $player) {
				$results[] = get_riotid_for_player($player["OPL_ID"]);
				sleep(1);
			}
		}
	}
}


$updates = 0;
foreach ($results as $result) {
	if ($result["updated"]) $updates++;
}

file_put_contents("cron_logs/cron_log_$day.log","$updates RiotIDs updated\n"."----- RiotIDs done -----\n", FILE_APPEND);

echo "-------- $updates RiotIDs updated\n";
$dbcn->close();