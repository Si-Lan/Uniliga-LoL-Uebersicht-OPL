<?php
include_once __DIR__."/../setup/data.php";
include_once __DIR__."/../functions/fe-functions.php";

$dbcn = create_dbcn();

if ($dbcn -> connect_error)	exit("Database Connection failed");

$playerid = $_SERVER["HTTP_PLAYERID"] ?? $_GET['playerid'] ?? NULL;
if ($playerid === NULL) exit;

echo create_player_overview($dbcn, $playerid);