<?php
include_once __DIR__."/../setup/data.php";
include_once __DIR__."/../functions/fe-functions.php";

$pass = check_login();
?>
<!DOCTYPE html>
<html lang="de">
<?php

$lightmode = is_light_mode(true);

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
$wildcards = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType='wildcard' AND OPL_ID_top_parent = ? AND deactivated = FALSE ORDER BY number", [$tournamentID])->fetch_all(MYSQLI_ASSOC);
$second_ranked_split = get_second_ranked_split_for_tournament($dbcn, $tournamentID, string:true);
$current_split = get_current_ranked_split($dbcn,$tournamentID);
$use_second_split = ($second_ranked_split == $current_split);

if ($tournament == NULL) {
	echo create_html_head_elements(title: "Kein Turnier gefunden | Uniliga LoL - Übersicht");
	echo "<body class='$lightmode'>";
	echo show_old_url_warning($tournamentID);
	echo create_header(title: "error");
	echo "<div style='text-align: center'>Kein Turnier unter der angegebenen ID gefunden!</div></body>";
	exit();
}

$t_name_clean = preg_replace("/LoL\s/i","",$tournament["name"]);
echo create_html_head_elements(css: ["elo"], title: "Elo-Übersicht - $t_name_clean | Uniliga LoL - Übersicht");

?>
<body class="elo-overview <?php echo $lightmode?>">
<?php

echo create_header($dbcn, title: "tournament", tournament_id: $tournamentID);

echo create_tournament_nav_buttons(tournament_id: $tournament_url_path, dbcn: $dbcn, active: "elo");

?>
<main>
<?php

echo "<h2 class='pagetitle'>Elo/Rang-Übersicht</h2>";

$stage_loaded = $_REQUEST['stage'] ?? null;
if ($stage_loaded == null) {
    if (count($leagues)>0) {
        $stage_loaded = "groups";
        $groups_active = "active";
    } else {
        $groups_active = "";
    }
    if (count($wildcards)>0 && count($leagues)==0) {
        $stage_loaded = "wildcard";
        $wildcard_active = "active";
    } else {
        $wildcard_active = "";
    }

} else {
    if ($stage_loaded == "wildcard" && count($wildcards)>0) {
        $wildcard_active = "active";
        $groups_active = "";
    } else {
        $wildcard_active = "";
        $groups_active = "active";
    }
}
?>
    <div id="elolist_switch_stage_buttons" <?php if (count($leagues) == 0 || count($wildcards) == 0) echo "style='display:none'" ?>>
            <button type="button" class="elolist_switch_stage <?php echo $wildcard_active?>" data-stage="wildcard" data-tournament="<?php echo $tournamentID ?>">Wildcard-Turnier</button>
            <button type="button" class="elolist_switch_stage <?php echo $groups_active?>" data-stage="groups" data-tournament="<?php echo $tournamentID ?>">Gruppenphase</button>
    </div>
<?php

echo "<div class='searchbar'>
                <span class='material-symbol search-icon' title='Suche'>".file_get_contents(__DIR__."/../icons/material/search.svg")."</span>
                <input class=\"search-teams-elo $tournamentID deletable-search\" oninput='search_teams_elo()' placeholder='Team suchen' type='search'>
                <button type='button' class='material-symbol search-clear' title='Suche leeren'>". file_get_contents(__DIR__."/../icons/material/close.svg") ."</button>
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

?>
            <div class='filter-button-wrapper'>
                <button class='filterb all-teams<?php echo $active_all?>' onclick='switch_elo_view("<?php echo $tournamentID?>","all-teams")'>Alle Ligen</button>
                <button class='filterb div-teams<?php echo $active_div?>' onclick='switch_elo_view("<?php echo $tournamentID?>","div-teams")'>Pro Liga</button>
                <button class='filterb group-teams<?php echo $active_group?>' onclick='switch_elo_view("<?php echo $tournamentID?>","group-teams")' <?php if ($stage_loaded != "groups") echo "style='display: none'" ?>>Pro Gruppe</button>
            </div>
<?php
if (isset($_GET['colored'])) {
	echo "
            <div class='settings-button-wrapper'>
                <button onclick='color_elo_list()'><input type='checkbox' name='coloring' checked class='controlled color-checkbox'><span>Nach $color_by einfärben</span></button>
            </div>";
	$color = " colored-list";
} else {
	echo "
            <div class='settings-button-wrapper'>
                <button onclick='color_elo_list()'><input type='checkbox' name='coloring' class='controlled color-checkbox'><span>Nach $color_by einfärben</span></button>
            </div>";
	$color = "";
}
if (($filtered == "liga" || $filtered == "gruppe") && $stage_loaded == "groups") {
	$jbutton_hide = "";
} else {
	$jbutton_hide = " style=\"display: none;\"";
}
echo "
            <div class='jump-button-wrapper'$jbutton_hide>";
foreach ($leagues as $league) {
	$div_num = $league['number'];
	echo "<button onclick='jump_to_league_elo(\"{$league['number']}\")'>Zu Liga {$league['number']}</button>";
}
echo "
            </div>";
echo "
            <div class='team-popup-bg' onclick='close_popup_team(event)'>
                            <div class='team-popup'></div>
            </div>";
echo "
            <div class='main-content$color'>";
if ($filtered == "liga" && $stage_loaded == "groups") {
	foreach ($leagues as $league) {
		echo generate_elo_list($dbcn,"div",$tournamentID,$league["OPL_ID"],second_ranked_split: $use_second_split);
	}
} elseif ($filtered == "gruppe" && $stage_loaded == "groups") {
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
} elseif ($filtered == "liga" && $stage_loaded == "wildcard") {
    foreach ($wildcards as $wildcard) {
        echo generate_elo_list($dbcn,"wildcard",$tournamentID,$wildcard["OPL_ID"],second_ranked_split: $use_second_split);
    }
} elseif ($stage_loaded == "wildcard") {
    echo generate_elo_list($dbcn,"all-wildcard",$tournamentID,second_ranked_split: $use_second_split);
} else {
	echo generate_elo_list($dbcn,"all",$tournamentID,second_ranked_split: $use_second_split);
}
echo "
            </div>"; // main-content
echo "<a class='button totop' onclick='to_top()' style='opacity: 0; pointer-events: none;'><div class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/expand_less.svg") ."</div></a>";


?>
</main>
</body>
</html>