<?php
include_once dirname(__DIR__,2)."/config/data.php";
include_once dirname(__DIR__,2)."/src/old_functions/fe-functions.php";

$dbcn = create_dbcn();

if ($dbcn -> connect_error)	exit("Database Connection failed");

$search = $_SERVER['HTTP_SEARCH'] ?? $_GET['search'] ?? NULL;
if ($search != NULL) {
    create_player_search_cards_from_search($dbcn, $search);
    exit;
}

$players = $_SERVER['HTTP_PLAYERS'] ?? $_GET['players'] ?? NULL;
if ($players != NULL) {
    $players = json_decode($players);
    create_player_search_cards($dbcn, $players,true);
    exit;
}

$dbcn->close();