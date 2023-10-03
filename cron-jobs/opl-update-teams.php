<?php
include_once __DIR__."/../admin/functions/get-opl-data.php";
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

echo "\n---- getting Teams from OPL\n";

$results = [];
$leagues = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'league'", [$tournament_id])->fetch_all(MYSQLI_ASSOC);
foreach ($leagues as $league) {
	$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'group'", [$league["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
	foreach ($groups as $group) {
		array_push($results, ...get_teams_for_tournament($group["OPL_ID"]));
		sleep(1);
	}
}

$writes = $updates = $dl = 0;
foreach ($results as $result) {
	if ($result["written"]) $writes++;
	if (count($result["updated"])) $updates++;
	if ($result["logo_downloaded"]) $dl++;
}

echo "-------- $writes Teams written\n";
echo "-------- $updates Teams updated\n";
echo "-------- $dl Team-Logos downloaded\n>";