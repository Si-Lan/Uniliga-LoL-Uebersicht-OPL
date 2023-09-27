<?php
include_once __DIR__."/../setup/data.php";

$dbcn = create_dbcn();

$type = $_SERVER["HTTP_TYPE"] ?? $_REQUEST["type"] ?? NULL;
if ($type == NULL) exit;

if ($type == "groups") {
	$leagueID = $_SERVER["HTTP_LEAGUEID"] ?? NULL;
	$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType =  'group' AND OPL_ID_parent = ?", [$leagueID])->fetch_all(MYSQLI_ASSOC);
	echo json_encode($groups);
}

$dbcn->close();