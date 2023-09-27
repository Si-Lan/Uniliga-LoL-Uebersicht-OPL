<?php
$root = __DIR__."/../../";
include_once $root."admin/functions/get-opl-data.php";
include_once $root."setup/data.php";

$type = $_SERVER["HTTP_TYPE"] ?? NULL;
if ($type == NULL) exit;

// gets the given tournament from OPL (1 Call to OPL-API)
if ($type == "get_tournament") {
	$id = $_SERVER["HTTP_ID"] ?? NULL;
	if (strlen($id) == 0) {
		echo "{}";
		exit;
	}
	$result = get_tournament($id);
	echo json_encode($result);
}

// adds the given tournament to DB
if ($type == "write_tournament") {
	$data = $_SERVER["HTTP_DATA"] ?? NULL;
	$data = json_decode($data,true);
	$result = write_tournament($data);
	echo $result;
}

// adds the given tournament to DB (x Calls to OPL-API) (1 for groups / more for tournaments and leagues)
if ($type == "get_teams_for_tournament") {
	$result = [];
	$dbcn = create_dbcn();
	$id = $_SERVER["HTTP_ID"] ?? NULL;
	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$id])->fetch_assoc();
	if ($tournament["eventType"] == "tournament") {
		$leagues = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'league'", [$id])->fetch_all(MYSQLI_ASSOC);
		foreach ($leagues as $league) {
			$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'group'", [$league["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
			foreach ($groups as $group) {
				array_push($result, ...get_teams_for_tournament($group["OPL_ID"]));
				sleep(1);
			}
		}
	} elseif ($tournament["eventType"] == "league") {
		$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'group'", [$id])->fetch_all(MYSQLI_ASSOC);
		foreach ($groups as $group) {
			array_push($result, ...get_teams_for_tournament($group["OPL_ID"]));
			sleep(1);
		}
	} else {
		array_push($result, ...get_teams_for_tournament($id));
	}
	echo json_encode($result);
	$dbcn->close();
}