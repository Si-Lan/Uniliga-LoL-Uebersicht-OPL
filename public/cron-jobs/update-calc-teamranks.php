<?php
$day = date("d_m_y");
ini_set("log_errors", 1);
ini_set("error_log", "cron_logs/cron_errors/riot_teamranks_$day.log");
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

echo "\n---- avg Ranks for Teams \n";
file_put_contents("cron_logs/cron_log_$day.log","\n----- Teamranks starting -----\n".date("d.m.y H:i:s")." : Teamranks for $tournament_id\n", FILE_APPEND);

$teams = $dbcn->execute_query("SELECT *
										FROM teams
										    JOIN teams_in_tournaments tit
										        on teams.OPL_ID = tit.OPL_ID_team
										WHERE OPL_ID_group IN
										      (SELECT OPL_ID
										       FROM tournaments
										       WHERE (eventType='group' OR (eventType='league' AND format='swiss'))
										         AND OPL_ID_top_parent = ?)", [$tournament_id])->fetch_all(MYSQLI_ASSOC);
$teams_updated = 0;
foreach ($teams as $tindex=>$team) {
	$result = calculate_avg_team_rank($team['OPL_ID'],$tournament_id);
	$teams_updated += $result['writes'];
}
echo "-------- ".$teams_updated." avg Ranks for Teams updated\n";
file_put_contents("cron_logs/cron_log_$day.log","$teams_updated Ranks for Teams updated\n"."----- Teamranks done -----\n", FILE_APPEND);