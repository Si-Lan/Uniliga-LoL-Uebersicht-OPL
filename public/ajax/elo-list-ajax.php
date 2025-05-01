<?php
include_once dirname(__DIR__,2)."/config/data.php";
include_once dirname(__DIR__,2)."/src/functions/fe-functions.php";

$dbcn = create_dbcn();

if ($dbcn -> connect_error) exit("Database Connection failed");

$tournamentID = $_SERVER['HTTP_TOURNAMENTID'] ?? $_GET['tournament'] ?? NULL;
if ($tournamentID == NULL) exit;

$type = $_SERVER['HTTP_TYPE'] ?? $_GET['type'] ?? NULL;
if ($type == NULL) exit;

$stage = $_SERVER['HTTP_STAGE'] ?? $_GET['stage'] ?? "groups";

$leagues = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType='league' AND OPL_ID_parent = ? ORDER BY Number",[$tournamentID])->fetch_all(MYSQLI_ASSOC);
$wildcards = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType='wildcard' AND OPL_ID_top_parent = ? ORDER BY number",[$tournamentID])->fetch_all(MYSQLI_ASSOC);

$second_ranked_split = get_second_ranked_split_for_tournament($dbcn, $tournamentID, string:true);
$current_split = get_current_ranked_split($dbcn,$tournamentID);
$use_second_split = ($second_ranked_split == $current_split);

if ($type == "all" && $stage == "groups") {
	echo generate_elo_list($dbcn,"all",$tournamentID,second_ranked_split: $use_second_split);
} elseif ($type == "div" && $stage == "groups") {
	foreach ($leagues as $league) {
		echo generate_elo_list($dbcn,"div",$tournamentID,$league["OPL_ID"],second_ranked_split: $use_second_split);
	}
} elseif ($type == "group" && $stage == "groups") {
	foreach ($leagues as $league) {
		if ($league["format"] == "swiss") {
			echo generate_elo_list($dbcn,"group",$tournamentID,$league["OPL_ID"],$league["OPL_ID"],second_ranked_split: $use_second_split);
			continue;
		}
		$groups_of_div = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType='group' AND OPL_ID_parent = ? ORDER BY Number",[$league['OPL_ID']])->fetch_all(MYSQLI_ASSOC);
		foreach ($groups_of_div as $group) {
			echo generate_elo_list($dbcn,"group",$tournamentID,$league["OPL_ID"],$group["OPL_ID"],second_ranked_split: $use_second_split);
		}
	}
} elseif ($type == "all" && $stage == "wildcard") {
    echo generate_elo_list($dbcn,"all-wildcard",$tournamentID,second_ranked_split: $use_second_split);
} elseif ($type == "div" && $stage == "wildcard") {
    foreach ($wildcards as $wildcard) {
        echo generate_elo_list($dbcn,"wildcard",$tournamentID,$wildcard["OPL_ID"],second_ranked_split: $use_second_split);
    }
}
$dbcn->close();