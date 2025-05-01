<?php
/** @var mysqli $dbcn  */

$tournament_url_path = $_GET["tournament"] ?? NULL;
$groupID= $_GET["group"] ?? NULL;

$tournamentID = $tournament_url_path;
if (preg_match("/^(winter|sommer)([0-9]{2})$/",strtolower($tournamentID),$url_path_matches)) {
	$split = $url_path_matches[1];
	$season = $url_path_matches[2];
	$tournamentID = $dbcn->execute_query("SELECT OPL_ID FROM tournaments WHERE season = ? AND split = ? AND eventType = 'tournament'", [$season, $split])->fetch_column();
}
$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ? AND eventType = 'tournament'", [$tournamentID])->fetch_assoc();
$group = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ? AND eventType = 'group'", [$groupID])->fetch_assoc();
if ($group == null) {
    $group = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ? AND eventType = 'league' AND format = 'swiss'", [$groupID])->fetch_assoc();
}
$swiss = (($group["format"]??"") == 'swiss');

if ($tournament == NULL || $group == NULL) {
	$_GET["error"] = "404";
	$_GET["404type"] = "group";
	$_GET["groupid"] = $groupID;
	require "error.php";
	echo "</html>";
	exit();
}

if ($swiss) {
    $league = $group;
} else {
	$league = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ? AND eventType = 'league'", [$group["OPL_ID_parent"]])->fetch_assoc();
}

$t_name_clean = preg_replace("/LoL\s/i","",$tournament["name"]);
if ($swiss) {
	echo create_html_head_elements(css: ["game"], title: "Liga {$league["number"]} - Swiss-Gruppe | $t_name_clean | Uniliga LoL - Übersicht");
} else {
	echo create_html_head_elements(css: ["game"], title: "Liga {$league["number"]} - Gruppe {$group["number"]} | $t_name_clean | Uniliga LoL - Übersicht");
}

$open_popup = "";
if (isset($_GET['match'])) {
	$open_popup = "popup_open";
}

?>
<body class="group <?=is_light_mode(true)." $open_popup"?>">
<?php

$pageurl = $_SERVER['REQUEST_URI'];
$opl_tourn_url = "https://www.opleague.pro/event/";

$teams_from_groupDB = $dbcn->execute_query("SELECT * FROM teams JOIN teams_in_tournaments tit ON teams.OPL_ID = tit.OPL_ID_team WHERE tit.OPL_ID_group = ? ORDER BY standing",[$group['OPL_ID']])->fetch_all(MYSQLI_ASSOC);
$teams_from_group = [];
foreach ($teams_from_groupDB as $i=>$team_from_group) {
	$teams_from_group[$team_from_group['OPL_ID']] = array("name"=>$team_from_group['name'], "OPL_ID_logo"=>$team_from_group['OPL_ID_logo']);
}
if ($league["format"] === "swiss") {
	$group_title = "Swiss-Gruppe";
} else {
	$group_title = "Gruppe {$group['number']}";
}

echo create_header(dbcn: $dbcn, title: "tournament", tournament_id: $tournamentID);

echo create_tournament_nav_buttons($tournamentID, $dbcn,"group",$league['OPL_ID'],$groupID);

if (!$tournament["archived"]) {

	$last_user_update = $dbcn->execute_query("SELECT last_update FROM updates_user_group WHERE OPL_ID_group = ?", [$groupID])->fetch_column();
	$last_cron_update = $dbcn->execute_query("SELECT last_update FROM updates_cron WHERE OPL_ID_tournament = ?", [$tournamentID])->fetch_column();

	$last_update = max($last_user_update, $last_cron_update);

	if ($last_update == NULL) {
		$updatediff = "unbekannt";
	} else {
		$last_update = strtotime($last_update);
		$currtime = time();
		$updatediff = max_time_from_timestamp($currtime - $last_update);
	}
}

echo "<div class='pagetitlewrapper withupdatebutton'>
				<div class='pagetitle'>
					<h2 class='pagetitle'>Liga {$league['number']} - $group_title</h2>
                	<a href='$opl_tourn_url{$groupID}' target='_blank' class='opl-link'><div class='material-symbol'>".file_get_contents(__DIR__."/../icons/material/open_in_new.svg")."</div></a>
              	</div>";
if (!$tournament["archived"]) {
	echo "
              	<div class='updatebuttonwrapper'>
              		<button type='button' class='user_update user_update_group update_data material-symbol' data-group='$groupID'><div class='material-symbol'>" . file_get_contents(__DIR__ . "/../icons/material/sync.svg") . "</div></button>
					<span class='last-update'>letztes Update:<br>$updatediff</span>
				</div>";
}
echo "
              </div>";

echo "<main>";
echo create_standings($dbcn,$tournamentID,$groupID);
echo create_matchlist($dbcn,$tournamentID,$groupID);
echo "</main>";

?>
</body>