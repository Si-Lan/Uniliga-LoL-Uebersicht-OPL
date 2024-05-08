<?php
include_once __DIR__."/../setup/data.php";
include_once __DIR__."/../functions/fe-functions.php";

$pass = check_login();
?>
<!DOCTYPE html>
<html lang="de">
<?php

$lightmode = is_light_mode(true);

$pageurl = $_SERVER['REQUEST_URI'];

try {
	$dbcn = create_dbcn();
} catch (Exception $e) {
	echo create_html_head_elements(title: "Error");
	echo "<body class='$lightmode'>";
	echo create_header(title: "error");
	echo "<div style='text-align: center'>Database Connection failed</div></body>";
	exit();
}

$tournament_url_path = $_GET["tournament"] ?? NULL;
$tournamentID = $tournament_url_path;
if (preg_match("/^(winter|sommer)([0-9]{2})$/",strtolower($tournamentID),$url_path_matches)) {
	$split = $url_path_matches[1];
	$season = $url_path_matches[2];
	$tournamentID = $dbcn->execute_query("SELECT OPL_ID FROM tournaments WHERE season = ? AND split = ? AND eventType = 'tournament'", [$season, $split])->fetch_column();
}

$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ? AND eventType = 'tournament'", [$tournamentID])->fetch_assoc();
$leagues = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType='league' AND OPL_ID_parent = ? AND deactivated = FALSE ORDER BY number", [$tournamentID])->fetch_all(MYSQLI_ASSOC);

if ($tournament == NULL) {
	echo create_html_head_elements(title: "Kein Turnier gefunden | Uniliga LoL - Übersicht");
	echo "<body class='$lightmode'>";
	echo show_old_url_warning($tournamentID);
	echo create_header(title: "error");
	echo "<div style='text-align: center'>Kein Turnier unter der angegebenen ID gefunden!</div></body>";
	exit();
}

$t_name_clean = preg_replace("/LoL\s/","",$tournament["name"]);
echo create_html_head_elements(css: ["elo"], title: "Elo-Übersicht - $t_name_clean | Uniliga LoL - Übersicht");

?>
<body class="elo-overview <?php echo $lightmode?>">
<?php

echo create_header($dbcn, title: "tournament", tournament_id: $tournamentID);

echo create_tournament_nav_buttons(tournament_id: $tournament_url_path, active: "elo");

echo "<h2 class='pagetitle'>Elo/Rang-Übersicht</h2>";
echo "<div class='search-wrapper'>
                <span class='searchbar'>
                    <input class=\"search-teams-elo $tournamentID deletable-search\" oninput='search_teams_elo()' placeholder='Team suchen' type='text'>
                    <a class='material-symbol clear-search' href='#'>". file_get_contents(__DIR__."/../icons/material/close.svg") ."</a>
                </span>
              </div>";
$filtered = $_REQUEST['view'] ?? NULL;
$active_all = "";
$active_div = "";
$active_group = "";
if ($filtered === "liga") {
	$active_div = " active";
	$color_by = "Rang";
} elseif ($filtered === "gruppe") {
	$active_group = " active";
	$color_by = "Rang";
} else {
	$active_all = " active";
	$color_by = "Liga";
}
echo "
            <div class='filter-button-wrapper'>
                <a class='button filterb all-teams$active_all' onclick='switch_elo_view(\"{$tournamentID}\",\"all-teams\")' href='turnier/$tournament_url_path/elo'>Alle Ligen</a>
                <a class='button filterb div-teams$active_div' onclick='switch_elo_view(\"{$tournamentID}\",\"div-teams\")' href='turnier/$tournament_url_path/elo?view=liga'>Pro Liga</a>
                <a class='button filterb group-teams$active_group' onclick='switch_elo_view(\"{$tournamentID}\",\"group-teams\")' href='turnier/$tournament_url_path/elo?view=gruppe'>Pro Gruppe</a>
            </div>";
if (isset($_GET['colored'])) {
	echo "
            <div class='settings-button-wrapper'>
                <a class='button' onclick='color_elo_list()' href='$pageurl'><input type='checkbox' name='coloring' checked class='controlled color-checkbox'><span>Nach $color_by einfärben</span></a>
            </div>";
	$color = " colored-list";
} else {
	echo "
            <div class='settings-button-wrapper'>
                <a class='button' onclick='color_elo_list()' href='$pageurl'><input type='checkbox' name='coloring' class='controlled color-checkbox'><span>Nach $color_by einfärben</span></a>
            </div>";
	$color = "";
}
if ($filtered == "liga" || $filtered == "gruppe") {
	$jbutton_hide = "";
} else {
	$jbutton_hide = " style=\"display: none;\"";
}
echo "
            <div class='jump-button-wrapper'$jbutton_hide>";
foreach ($leagues as $league) {
	$div_num = $league['number'];
	echo "<a class='button' onclick='jump_to_league_elo(\"{$league['number']}\")' href='$pageurl'>Zu Liga {$league['number']}</a>";
}
echo "
            </div>";
echo "
            <div class='team-popup-bg' onclick='close_popup_team(event)'>
                            <div class='team-popup'></div>
            </div>";
echo "
            <div class='main-content$color'>";
if ($filtered == "liga") {
	foreach ($leagues as $league) {
		echo generate_elo_list($dbcn,"div",$tournamentID,$league["OPL_ID"]);
	}
} elseif ($filtered == "gruppe") {
	foreach ($leagues as $league) {
        if ($league["format"] == "swiss") {
            echo generate_elo_list($dbcn,"group",$tournamentID,$league["OPL_ID"],$league["OPL_ID"]);
            continue;
        }
		$groups_of_div = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType='group' AND OPL_ID_parent = ? ORDER BY Number",[$league['OPL_ID']])->fetch_all(MYSQLI_ASSOC);
		foreach ($groups_of_div as $group) {
			echo generate_elo_list($dbcn,"group",$tournamentID,$league["OPL_ID"],$group["OPL_ID"]);
		}
	}
} else {
	echo generate_elo_list($dbcn,"all",$tournamentID);
}
echo "
            </div>"; // main-content
echo "<a class='button totop' onclick='to_top()' style='opacity: 0; pointer-events: none;'><div class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/expand_less.svg") ."</div></a>";


?>

</body>
</html>