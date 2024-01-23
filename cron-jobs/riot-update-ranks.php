<?php
$day = date("d_m_y");
ini_set("log_errors", 1);
ini_set("error_log", "cron_logs/cron_errors/riot_ranks_$day.log");
include_once __DIR__."/../admin/functions/get-rgapi-data.php";
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

$amount = $_GET['amount'] ?? null;
$index = $_GET['index'] ?? null;

$indexed = ($amount != null && $index != null);
$first = $amount*$index;

echo "\n---- Ranks for Players \n";
if ($indexed) {
	file_put_contents("cron_logs/cron_log_$day.log","\n----- Ranks starting -----\n".date("d.m.y H:i:s")." : Ranks for $tournament_id\nPlayers ".$first."-".$first+($amount-1)."\n", FILE_APPEND);
} else {
	file_put_contents("cron_logs/cron_log_$day.log","\n----- Ranks starting -----\n".date("d.m.y H:i:s")." : Ranks for $tournament_id\n", FILE_APPEND);
}

if ($indexed) {
	$players = $dbcn->execute_query("SELECT p.*
										FROM players p
										    JOIN players_in_teams_in_tournament pit
										        ON p.OPL_ID = pit.OPL_ID_player
										    JOIN teams_in_tournaments tit
										        ON pit.OPL_ID_team = tit.OPL_ID_team
										WHERE tit.OPL_ID_group IN
										      (SELECT OPL_ID
										       FROM tournaments
										       WHERE eventType='group'
										         AND OPL_ID_parent IN
										             (SELECT OPL_ID
										              FROM tournaments
										              WHERE eventType='league'
										                AND OPL_ID_parent = ?
										              )
										       )
										ORDER BY p.OPL_ID
										LIMIT ? OFFSET ?", [$tournament_id,$amount,$first])->fetch_all(MYSQLI_ASSOC);
} else {
	$players = $dbcn->execute_query("SELECT p.*
										FROM players p
										    JOIN players_in_teams_in_tournament pit
										        ON p.OPL_ID = pit.OPL_ID_player
										    JOIN teams_in_tournaments tit
										        ON pit.OPL_ID_team = tit.OPL_ID_team
										WHERE tit.OPL_ID_group IN
										      (SELECT OPL_ID
										       FROM tournaments
										       WHERE eventType='group'
										         AND OPL_ID_parent IN
										             (SELECT OPL_ID
										              FROM tournaments
										              WHERE eventType='league'
										                AND OPL_ID_parent = ?
										              )
										       )
										ORDER BY p.OPL_ID", [$tournament_id])->fetch_all(MYSQLI_ASSOC);
}
$players_updated = 0;
foreach ($players as $pindex=>$player) {
	if ($pindex % 50 === 0 && $pindex != 0) {
		if ($indexed) {
			file_put_contents("cron_logs/cron_log_$day.log",date("d.m.y H:i:s")." : ".$pindex+$first." Players done\n", FILE_APPEND);
		} else {
			file_put_contents("cron_logs/cron_log_$day.log",date("d.m.y H:i:s")." : $pindex Players done\n", FILE_APPEND);
		}
		sleep(10);
	}
	$result = get_Rank_by_SummonerId($player['OPL_ID']);
	$players_updated += $result["writes"];
}
file_put_contents("cron_logs/cron_log_$day.log","$players_updated Ranks for Players updated\n"."----- Ranks done -----\n", FILE_APPEND);
echo "-------- ".$players_updated." Ranks for Players updated\n";

/*
echo "\n---- avg Ranks for Teams \n";
file_put_contents("cron_logs/cron_log_$day.log","\n----- Teamranks starting -----\n".date("d.m.y H:i:s")." : Teamranks for $tournament_id\n", FILE_APPEND);

$teams = $dbcn->execute_query("SELECT * FROM teams JOIN teams_in_tournaments tit on teams.OPL_ID = tit.OPL_ID_team WHERE OPL_ID_group IN (SELECT OPL_ID FROM tournaments WHERE eventType='group' AND OPL_ID_parent IN (SELECT OPL_ID FROM tournaments WHERE eventType='league' AND OPL_ID_parent = ?))", [$tournament_id])->fetch_all(MYSQLI_ASSOC);
$teams_updated = 0;
foreach ($teams as $tindex=>$team) {
	$result = calculate_avg_team_rank($team['OPL_ID'],$tournament_id);
	$teams_updated += $result['writes'];
}
echo "-------- ".$teams_updated." avg Ranks for Teams updated\n";
file_put_contents("cron_logs/cron_log_$day.log","$teams_updated Ranks for Teams updated\n"."----- Teamranks done -----\n", FILE_APPEND);
*/