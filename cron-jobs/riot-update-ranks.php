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

echo "<br>---- Ranks for Players <br>";
$players = $dbcn->execute_query("SELECT p.* FROM players p JOIN players_in_teams pit on p.OPL_ID = pit.OPL_ID_player JOIN teams_in_tournaments tit on pit.OPL_ID_team = tit.OPL_ID_team WHERE tit.OPL_ID_tournament IN (SELECT OPL_ID FROM tournaments WHERE eventType='group' AND OPL_ID_parent IN (SELECT OPL_ID FROM tournaments WHERE eventType='league' AND OPL_ID_parent = ?))", [$tournament_id])->fetch_all(MYSQLI_ASSOC);
$players_updated = 0;
foreach ($players as $pindex=>$player) {
	if ($pindex % 50 === 0 && $pindex != 0) {
		sleep(10);
	}
	$result = get_Rank_by_SummonerId($player['OPL_ID']);
	$players_updated += $result["writes"];
}
echo "-------- ".$players_updated." Ranks for Players updated<br>";

echo "<br>---- avg Ranks for Teams <br>";
$teams = $dbcn->execute_query("SELECT * FROM teams JOIN teams_in_tournaments tit on teams.OPL_ID = tit.OPL_ID_team WHERE OPL_ID_tournament IN (SELECT OPL_ID FROM tournaments WHERE eventType='group' AND OPL_ID_parent IN (SELECT OPL_ID FROM tournaments WHERE eventType='league' AND OPL_ID_parent = ?))", [$tournament_id])->fetch_all(MYSQLI_ASSOC);
$teams_updated = 0;
foreach ($teams as $tindex=>$team) {
	$result = calculate_avg_team_rank($team['TeamID']);
	$teams_updated += $result['writes'];
}
echo "-------- ".$teams_updated." avg Ranks for Teams updated<br>";