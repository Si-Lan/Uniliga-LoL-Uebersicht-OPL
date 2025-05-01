<?php
include_once dirname(__DIR__,2)."/config/data.php";
include_once dirname(__DIR__,2)."/src/functions/fe-functions.php";

$dbcn = create_dbcn();

if ($dbcn -> connect_error)	exit("Database Connection failed");

$playerid = $_SERVER["HTTP_PLAYERID"] ?? $_GET['playerid'] ?? NULL;
if ($playerid === NULL) exit;

echo create_player_overview($dbcn, $playerid);