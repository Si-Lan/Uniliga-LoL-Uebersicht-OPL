<?php
include_once __DIR__.'/../setup/data.php';
include __DIR__.'/../functions/fe-functions.php';

$dbcn = create_dbcn();

if ($dbcn -> connect_error) exit("Database Connection failed");

$tournamentID = $_SERVER['HTTP_TOURNAMENTID'] ?? $_GET['tournament'] ?? NULL;
if ($tournamentID == NULL) exit;

$type = $_SERVER['HTTP_TYPE'] ?? $_GET['type'] ?? NULL;
if ($type == NULL) exit;

$leagues = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType='league' AND OPL_ID_parent = ? ORDER BY Number",[$tournamentID])->fetch_all(MYSQLI_ASSOC);

if ($type == "all") {
	echo generate_elo_list($dbcn,"all",$tournamentID);
} elseif ($type == "div") {
	foreach ($leagues as $league) {
		echo generate_elo_list($dbcn,"div",$tournamentID,$league["OPL_ID"]);
	}
} elseif ($type == "group") {
	foreach ($leagues as $league) {
		if ($league["format"] == "swiss") {
			echo generate_elo_list($dbcn,"group",$tournamentID,$league["OPL_ID"],$league["OPL_ID"]);
			continue;
		}
		$groups_of_div = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType='group' AND OPL_ID_parent = ? ORDER BY Number",[$league['OPL_ID']])->fetch_all(MYSQLI_ASSOC);
		foreach ($groups_of_div as $group) {
			echo generate_elo_list($dbcn,"group",$tournamentID,$league["OPL_ID"],$group["OPL_ID"]);
		}
	}
}
$dbcn->close();