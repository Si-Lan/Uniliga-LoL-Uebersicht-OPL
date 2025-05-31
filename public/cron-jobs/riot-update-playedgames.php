<?php
$day = date("d_m_y");
ini_set("log_errors", 1);
ini_set("error_log", "cron_logs/cron_errors/riot_playedgames_$day.log");
include_once dirname(__DIR__,2)."/src/old_functions/admin/get-rgapi-data.php";
include_once dirname(__DIR__,2).'/config/data.php';
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

echo "\n---- all played Custom-Games from Players \n";
if ($indexed) {
	file_put_contents("cron_logs/cron_log_$day.log","\n----- played Custom-Games starting -----\n".date("d.m.y H:i:s")." : played Custom-Games for $tournament_id\nPlayers ".$first."-".$first+($amount-1)."\n", FILE_APPEND);
} else {
	file_put_contents("cron_logs/cron_log_$day.log","\n----- played Custom-Games starting -----\n".date("d.m.y H:i:s")." : played Custom-Games for $tournament_id\n", FILE_APPEND);
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
										       WHERE (eventType='group' OR (eventType='league' AND format='swiss'))
										         AND OPL_ID_top_parent = ?)
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
										       WHERE (eventType='group' OR (eventType='league' AND format='swiss'))
										         AND OPL_ID_top_parent = ?)
										ORDER BY p.OPL_ID", [$tournament_id])->fetch_all(MYSQLI_ASSOC);
}
$games_gotten = array("already"=>0,"new"=>0);
foreach ($players as $pindex=>$player) {
	if (($pindex) % 50 === 0 && $pindex != 0) {
		if ($indexed) {
			file_put_contents("cron_logs/cron_log_$day.log",date("d.m.y H:i:s")." : ".$pindex+$first." Players done\n", FILE_APPEND);
		} else {
			file_put_contents("cron_logs/cron_log_$day.log",date("d.m.y H:i:s")." : $pindex Players done\n", FILE_APPEND);
		}
		sleep(10);
	}
	$results = get_games_by_player($player['OPL_ID'], $tournament_id);
	$games_gotten["new"] += $results["writes"];
	$games_gotten["already"] += $results["already"];
}

file_put_contents("cron_logs/cron_log_$day.log","{$games_gotten["new"]} new Games written\n{$games_gotten["already"]} found Games already in Database\n"."----- played Custom-Games done -----\n", FILE_APPEND);

echo "-------- {$games_gotten["new"]} new Games written\n";
echo "-------- {$games_gotten["already"]} found Games already in Database\n";