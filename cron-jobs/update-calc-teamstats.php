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

echo "<br>---- calculate Teamstats <br>";
$teams = $dbcn->execute_query("SELECT * FROM teams JOIN teams_in_tournaments tit on teams.OPL_ID = tit.OPL_ID_team WHERE OPL_ID_tournament IN (SELECT OPL_ID FROM tournaments WHERE eventType='group' AND OPL_ID_parent IN (SELECT OPL_ID FROM tournaments WHERE eventType='league' AND OPL_ID_parent = ?))", [$tournament_id])->fetch_all(MYSQLI_ASSOC);
$teamstats_gotten = array("writes"=>0,"updates"=>0,"not"=>0);
foreach ($teams as $tindex=>$team) {
    $result = calculate_teamstats($team["OPL_ID"],$tournament_id);
    $teamstats_gotten["writes"] += $result["writes"];
    $teamstats_gotten["updates"] += $result["updates"];
    $teamstats_gotten["not"] += $result["without"];
}
echo "-------- written Stats for {$teamstats_gotten["writes"]} Teams<br>";
echo "-------- updated Stats for {$teamstats_gotten["updates"]} Teams<br>";
echo "-------- no Games played for {$teamstats_gotten["not"]} Teams<br>";