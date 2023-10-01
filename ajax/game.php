<?php
include_once __DIR__."/../setup/data.php";
include_once __DIR__."/../functions/fe-functions.php";

$dbcn = create_dbcn();

if ($dbcn -> connect_error) exit("Database Connection failed");

$gameID = $_SERVER['HTTP_GAMEID'] ?? $_GET['gameID'] ?? NULL;
if ($gameID === NULL) exit("no game found");
$teamID = $_SERVER['HTTP_TEAMID'] ?? $_GET['teamID'] ?? NULL;
echo create_game($dbcn, $gameID, $teamID);

$dbcn->close();