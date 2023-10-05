<?php
include_once __DIR__."/../admin/functions/get-opl-data.php";
include_once __DIR__.'/../setup/data.php';
$dbcn = create_dbcn();

if ($dbcn->connect_error) {
	echo "Database Connection failed";
	exit;
}
if (!(isset($_GET['t'])) && !isset($_GET['g'])) {
	exit;
}
$tournament_id = $_GET['t'] ?? NULL;
$group_id = $_GET['g'] ?? NULL;

echo "\n---- getting Summonernames for Players from OPL \n";
$results = [];

if ($group_id != NULL) {
	$players = $dbcn->execute_query("SELECT * FROM players JOIN players_in_teams pit on players.OPL_ID = pit.OPL_ID_player JOIN teams_in_tournaments tit on pit.OPL_ID_team = tit.OPL_ID_team WHERE OPL_ID_tournament = ?", [$group_id])->fetch_all(MYSQLI_ASSOC);
	foreach ($players as $player) {
		$results[] = get_summonerNames_for_player($player["OPL_ID"]);
		sleep(1);
	}
} elseif ($tournament_id != NULL) {
	$leagues = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'league'", [$tournament_id])->fetch_all(MYSQLI_ASSOC);
	foreach ($leagues as $league) {
		$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'group'", [$league["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
		foreach ($groups as $group) {
			$players = $dbcn->execute_query("SELECT * FROM players JOIN players_in_teams pit on players.OPL_ID = pit.OPL_ID_player JOIN teams_in_tournaments tit on pit.OPL_ID_team = tit.OPL_ID_team WHERE OPL_ID_tournament = ?", [$group["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
			foreach ($players as $player) {
				$results[] = get_summonerNames_for_player($player["OPL_ID"]);
				sleep(1);
			}
		}
	}
}


$updates = 0;
foreach ($results as $result) {
	if ($result["updated"]) $updates++;
}

echo "-------- $updates Summonernames updated\n";
$dbcn->close();