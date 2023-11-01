<?php
set_time_limit(600);
$day = date("d_m_y");
ini_set("log_errors", 1);
ini_set("error_log", "cron_logs/cron_errors/calc_roles_$day.log");
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

echo "\n---- calculate Roles of Players \n";
file_put_contents("cron_logs/cron_log_$day.log","\n----- calculating Roles starting -----\n".date("d.m.y H:i:s")." : calculating Roles for $tournament_id\n", FILE_APPEND);

$teams = $dbcn->execute_query("SELECT * FROM teams JOIN teams_in_tournaments tit on teams.OPL_ID = tit.OPL_ID_team WHERE OPL_ID_group IN (SELECT OPL_ID FROM tournaments WHERE eventType='group' AND OPL_ID_parent IN (SELECT OPL_ID FROM tournaments WHERE eventType='league' AND OPL_ID_parent = ?))", [$tournament_id])->fetch_all(MYSQLI_ASSOC);
$players_updated = 0;
foreach ($teams as $tindex=>$team) {
	$result = get_played_positions_for_players($team['OPL_ID'], $tournament_id);
	$players_updated += $result['writes'];
}
echo "-------- Roles for ".$players_updated." Players updated\n";
file_put_contents("cron_logs/cron_log_$day.log","\nRoles for $players_updated Players updated\n"."----- calculating Roles done -----\n", FILE_APPEND);
