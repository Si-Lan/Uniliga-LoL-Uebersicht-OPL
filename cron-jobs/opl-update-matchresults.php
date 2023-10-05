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

echo "\n---- getting Matchresults from OPL \n";
$results = [];

if ($group_id != NULL) {
	$matches = $dbcn->execute_query("SELECT * FROM matchups WHERE OPL_ID_tournament = ?", [$group_id])->fetch_all(MYSQLI_ASSOC);
	foreach ($matches as $match) {
		$results[] = get_results_for_matchup($match["OPL_ID"]);
		sleep(1);
	}
} elseif ($tournament_id != NULL) {
	$leagues = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'league'", [$tournament_id])->fetch_all(MYSQLI_ASSOC);
	foreach ($leagues as $league) {
		$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'group'", [$league["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
		foreach ($groups as $group) {
			$matches = $dbcn->execute_query("SELECT * FROM matchups WHERE OPL_ID_tournament = ?", [$group["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
			foreach ($matches as $match) {
				$results[] = get_results_for_matchup($match["OPL_ID"]);
				sleep(1);
			}
		}
	}
}


$updates = 0;
foreach ($results as $result) {
	if (count($result) > 0) $updates++;
}

echo "-------- $updates Matchresults updated\n";
$dbcn->close();