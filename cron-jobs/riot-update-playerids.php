<?php
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

echo "<br>---- PUUIDs and SummonerIDs for Players <br>";
$teams = $dbcn->execute_query("SELECT * FROM teams JOIN teams_in_tournaments tit on teams.OPL_ID = tit.OPL_ID_team WHERE OPL_ID_group IN (SELECT OPL_ID FROM tournaments WHERE eventType='group' AND OPL_ID_parent IN (SELECT OPL_ID FROM tournaments WHERE eventType='league' AND OPL_ID_parent = ?))", [$tournament_id])->fetch_all(MYSQLI_ASSOC);
$current_players_gotten = 0;
$ids_written = array("p"=>0,"s"=>0,"4"=>0);
foreach ($teams as $tindex=>$team) {
	$team_id = $team['OPL_ID'];
	$players_from_team = $dbcn->execute_query("SELECT * FROM players JOIN players_in_teams_in_tournament pit on players.OPL_ID = pit.OPL_ID_player AND pit.OPL_ID_tournament = ? WHERE OPL_ID_team = ? AND (PUUID IS NULL OR summonerID IS NULL)", [$tournament_id, $team_id])->fetch_all(MYSQLI_ASSOC);
	if ($current_players_gotten + count($players_from_team) > 50) {
		$current_players_gotten = 0;
		sleep(10);
	}
	$current_players_gotten += count($players_from_team);
	$results = get_puuids_by_team($team_id);
	$ids_written["p"] += $results["writesP"];
	$ids_written["s"] += $results["writesS"];
	$ids_written["4"] += $results["404"];
}
echo "-------- {$ids_written["p"]} PUUIDS written<br>";
echo "-------- {$ids_written["s"]} SummonerIDs written<br>";
echo "-------- {$ids_written["4"]} Summoners not found<br>";