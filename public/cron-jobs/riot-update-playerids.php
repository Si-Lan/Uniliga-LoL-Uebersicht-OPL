<?php
$day = date("d_m_y");
ini_set("log_errors", 1);
ini_set("error_log", "cron_logs/cron_errors/riot_playerids_$day.log");
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

echo "\n---- PUUIDs and SummonerIDs for Players \n";
file_put_contents("cron_logs/cron_log_$day.log","\n----- PUUIDs and SummonerIDs starting -----\n".date("d.m.y H:i:s")." : PUUIDs and SummonerIDs for $tournament_id\n", FILE_APPEND);

$teams = $dbcn->execute_query("SELECT *
										FROM teams
										    JOIN teams_in_tournaments tit
										        ON teams.OPL_ID = tit.OPL_ID_team
										WHERE OPL_ID_group IN
										      (SELECT OPL_ID
										       FROM tournaments
										       WHERE (eventType='group' OR (eventType='league' and format='swiss'))
										         AND OPL_ID_top_parent = ?)", [$tournament_id])->fetch_all(MYSQLI_ASSOC);
$current_players_gotten = 0;
$ids_written = array("p"=>0,"s"=>0,"4"=>0);
foreach ($teams as $tindex=>$team) {
	$team_id = $team['OPL_ID'];
	$players_from_team = $dbcn->execute_query("SELECT *
													 FROM players
													     JOIN players_in_teams_in_tournament pit
													         ON players.OPL_ID = pit.OPL_ID_player
													                AND pit.OPL_ID_tournament = ?
													 WHERE OPL_ID_team = ?
													   AND (PUUID IS NULL OR summonerID IS NULL)", [$tournament_id, $team_id])->fetch_all(MYSQLI_ASSOC);
	if ($current_players_gotten + count($players_from_team) > 50) {
		file_put_contents("cron_logs/cron_log_$day.log",date("d.m.y H:i:s")." : $tindex Teams done\n", FILE_APPEND);
		$current_players_gotten = 0;
		sleep(10);
	}
	$current_players_gotten += count($players_from_team);
	$results = get_puuids_by_team($team_id);
	$ids_written["p"] += $results["writesP"];
	$ids_written["s"] += $results["writesS"];
	$ids_written["4"] += $results["404"];
}

file_put_contents("cron_logs/cron_log_$day.log","{$ids_written["p"]} PUUIDS written\n{$ids_written["s"]} SummonerIDs written\n{$ids_written["4"]} Summoners not found\n"."----- PUUIDs and SummonerIDs done -----\n", FILE_APPEND);

echo "-------- {$ids_written["p"]} PUUIDS written\n";
echo "-------- {$ids_written["s"]} SummonerIDs written\n";
echo "-------- {$ids_written["4"]} Summoners not found\n";