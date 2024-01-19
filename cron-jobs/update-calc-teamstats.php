<?php
set_time_limit(600);
$day = date("d_m_y");
ini_set("log_errors", 1);
ini_set("error_log", "cron_logs/cron_errors/calc_teamstats_$day.log");
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

echo "\n---- calculate Teamstats \n";
file_put_contents("cron_logs/cron_log_$day.log","\n----- calculating Teamstats starting -----\n".date("d.m.y H:i:s")." : calculating Teamstats for $tournament_id\n", FILE_APPEND);

$teams = $dbcn->execute_query("SELECT * FROM teams JOIN teams_in_tournaments tit on teams.OPL_ID = tit.OPL_ID_team WHERE OPL_ID_group IN (SELECT OPL_ID FROM tournaments WHERE eventType='group' AND OPL_ID_parent IN (SELECT OPL_ID FROM tournaments WHERE eventType='league' AND OPL_ID_parent = ?))", [$tournament_id])->fetch_all(MYSQLI_ASSOC);
$teamstats_gotten = array("writes"=>0,"updates"=>0,"not"=>0);
foreach ($teams as $tindex=>$team) {
    $result = calculate_teamstats($team["OPL_ID"],$tournament_id);
    $teamstats_gotten["writes"] += $result["writes"];
    $teamstats_gotten["updates"] += $result["updates"];
    $teamstats_gotten["not"] += $result["without"];
}
echo "-------- written Stats for {$teamstats_gotten["writes"]} Teams\n";
echo "-------- updated Stats for {$teamstats_gotten["updates"]} Teams\n";
echo "-------- no Games played for {$teamstats_gotten["not"]} Teams\n";
file_put_contents("cron_logs/cron_log_$day.log","written Stats for {$teamstats_gotten["writes"]} Teams\nupdated Stats for {$teamstats_gotten["updates"]} Teams\nno Games played for {$teamstats_gotten["not"]} Teams\n"."----- calculating Teamstats done -----\n", FILE_APPEND);