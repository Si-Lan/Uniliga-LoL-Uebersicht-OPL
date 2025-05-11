<?php
require_once dirname(__DIR__,2)."/src/autoload.php";

use App\Components\Cards\SummonerCard;

include_once dirname(__DIR__,2)."/config/data.php";
include_once dirname(__DIR__,2)."/src/functions/fe-functions.php";
include_once dirname(__DIR__,2)."/src/functions/helper.php";

$dbcn = create_dbcn();

$type = $_SERVER["HTTP_TYPE"] ?? $_REQUEST["type"] ?? NULL;
if ($type == NULL) exit;

if ($type == "matchhistory") {
	$team_ID = $_SERVER["HTTP_TEAMID"] ?? $_REQUEST['team'] ?? NULL;
	$group_ID = $_SERVER["HTTP_GROUPID"] ?? $_REQUEST['group'] ?? NULL;
	$tournament_ID = $_SERVER["HTTP_TOURNAMENTID"] ?? $_REQUEST['tournament'] ?? NULL;

	create_matchhistory($dbcn, $tournament_ID, $group_ID, $team_ID);
}

$dbcn->close();