<?php
set_time_limit(600);
$day = date("d_m_y");
ini_set("log_errors", 1);
ini_set("error_log", "cron_logs/cron_errors/riot_playedgames_$day.log");
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

echo "\n---- all played Custom-Games from Players \n";
file_put_contents("cron_logs/cron_log_$day.log","\n----- played Custom-Games starting -----\n".date("d.m.y H:i:s")." : played Custom-Games for $tournament_id\n", FILE_APPEND);

$players = $dbcn->execute_query("SELECT p.* FROM players p JOIN players_in_teams_in_tournament pit ON p.OPL_ID = pit.OPL_ID_player JOIN teams_in_tournaments tit ON pit.OPL_ID_team = tit.OPL_ID_team WHERE tit.OPL_ID_group IN (SELECT OPL_ID FROM tournaments WHERE eventType='group' AND OPL_ID_parent IN (SELECT OPL_ID FROM tournaments WHERE eventType='league' AND OPL_ID_parent = ?))", [$tournament_id])->fetch_all(MYSQLI_ASSOC);
$games_gotten = array("already"=>0,"new"=>0);
foreach ($players as $pindex=>$player) {
	if (($pindex) % 50 === 0 && $pindex != 0) {
		file_put_contents("cron_logs/cron_log_$day.log",date("d.m.y H:i:s")." : $pindex Players done\n", FILE_APPEND);
		sleep(10);
	}
	$results = get_games_by_player($player['OPL_ID'], $tournament_id);
	$games_gotten["new"] += $results["writes"];
	$games_gotten["already"] += $results["already"];
}

file_put_contents("cron_logs/cron_log_$day.log","{$games_gotten["new"]} new Games written\n{$games_gotten["already"]} found Games already in Database\n"."----- played Custom-Games done -----\n", FILE_APPEND);

echo "-------- {$games_gotten["new"]} new Games written\n";
echo "-------- {$games_gotten["already"]} found Games already in Database\n";