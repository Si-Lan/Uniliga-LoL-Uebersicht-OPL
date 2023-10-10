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

echo "<br>---- all played Custom-Games from Players <br>";
$players = $dbcn->execute_query("SELECT p.* FROM players p JOIN players_in_teams pit on p.OPL_ID = pit.OPL_ID_player JOIN teams_in_tournaments tit on pit.OPL_ID_team = tit.OPL_ID_team WHERE tit.OPL_ID_tournament IN (SELECT OPL_ID FROM tournaments WHERE eventType='group' AND OPL_ID_parent IN (SELECT OPL_ID FROM tournaments WHERE eventType='league' AND OPL_ID_parent = ?))", [$tournament_id])->fetch_all(MYSQLI_ASSOC);
$games_gotten = array("already"=>0,"new"=>0);
foreach ($players as $pindex=>$player) {
	if (($pindex) % 50 === 0 && $pindex != 0) {
		sleep(10);
	}
	$results = get_games_by_player($player['OPL_ID'], $tournament_id);
	$games_gotten["new"] += $results["writes"];
	$games_gotten["already"] += $results["already"];
}
echo "-------- {$games_gotten["new"]} new Games written<br>";
echo "-------- {$games_gotten["already"]} found Games already in Database<br>";