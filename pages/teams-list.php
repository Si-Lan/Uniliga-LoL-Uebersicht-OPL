<?php
include_once __DIR__."/../setup/data.php";
include_once __DIR__."/../functions/fe-functions.php";

check_login();
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

if ($tournament == NULL) {
	echo create_html_head_elements(title: "Kein Turnier gefunden | Uniliga LoL - Übersicht");
	echo "<body class='$lightmode'>";
	echo show_old_url_warning($tournamentID);
	echo create_header(title: "error");
	echo "<div style='text-align: center'>Kein Turnier unter der angegebenen ID gefunden!</div></body>";
	exit();
}

$t_name_clean = preg_replace("/LoL\s/","",$tournament["name"]);
echo create_html_head_elements(title: "Team-Liste - $t_name_clean | Uniliga LoL - Übersicht");

?>
<body class="teamlist <?php echo $lightmode?>">
<?php

echo create_header($dbcn, title: "tournament", tournament_id: $tournamentID);

echo create_tournament_nav_buttons(tournament_id: $tournament_url_path, active: "list");

$leaguesDB = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'league'", [$tournamentID])->fetch_all(MYSQLI_ASSOC);
$leagues = $groups = [];
foreach ($leaguesDB as $league) {
	$leagues[$league["OPL_ID"]] = $league;
	$groupsDB = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'group'", [$league["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
	foreach ($groupsDB as $group) {
		$groups[$group["OPL_ID"]] = $group;
	}
}


echo "<h2 class='pagetitle'>Team-Liste</h2>";
echo "<div class='search-wrapper'>
                <span class='searchbar'>
                    <input class=\"search-teams $tournamentID deletable-search\" onkeyup='search_teams(\"$tournamentID\")' placeholder=\"Teams durchsuchen\" type=\"text\">
                    <a class='material-symbol clear-search' href='#'>". file_get_contents("../icons/material/close.svg") ."</a>
                </span>
              </div>";

if (isset($_GET["liga"])) {
	$filteredDivID = $_GET["liga"];
	$divallClass = "";
} else {
	$divallClass = "selected='selected'";
}
$toGroupButtonClass = "";
$toGroupButtonLink = "";
if (isset($filteredDivID) && isset($_GET["gruppe"])) {
	$filteredGroupID = $_GET["gruppe"];
	$groupallClass = "";
	$toGroupButtonClass = " shown";
	$toGroupButtonLink = " href='turnier/".$tournamentID."/gruppe/".$filteredGroupID."'";
} else {
	$groupallClass = "selected='selected'";
}
echo "<div class='team-filter-wrap'><h3>Filter:</h3>";
echo "<div class='slct div-select-wrap'>
                <select name='Ligen' class='divisions' onchange='filter_teams_list_division(this.value)'>
                    <option value='all' $divallClass>Alle Ligen</option>";
foreach ($leagues as $league) {
	if (isset($filteredDivID) && $filteredDivID == $league['OPL_ID']) {
		$divClass = " selected='selected'";
	} else {
		$divClass = "";
	}
	echo "<option value='".$league["OPL_ID"]."'$divClass>Liga ".$league["number"]."</option>";
}
echo "
                </select>
                <span class='material-symbol'>".file_get_contents("../icons/material/arrow_drop_down.svg")."</span>
              </div>
                <div class='slct groups-select-wrap'>
                    <select name='Gruppen' class='groups' onchange='filter_teams_list_group(this.value)'>
                        <option value='all' $groupallClass>Alle Gruppen</option>";
if (isset($filteredDivID)) {
	$groups_filteredDiv = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'group' ORDER BY Number",[$filteredDivID])->fetch_all(MYSQLI_ASSOC);
	foreach ($groups_filteredDiv as $group) {
		if (isset($filteredGroupID) && $filteredGroupID == $group['OPL_ID']) {
			$groupClass = " selected='selected'";
		} else {
			$groupClass = "";
		}
		echo "<option value='".$group["OPL_ID"]."'$groupClass>Gruppe ".$group["number"]."</option>";
	}
}
echo "
                    </select>
                    <span class='material-symbol'>".file_get_contents("../icons/material/arrow_drop_down.svg")."</span>
                </div>";
echo "<a class='button b-group$toGroupButtonClass'$toGroupButtonLink>zur Gruppe</a>";
echo "</div>";

echo "
            <div class='team-popup-bg' onclick='close_popup_team(event)'>
                <div class='team-popup'></div>
            </div>";
echo "<div class='team-list $tournamentID'>";
echo "<div class='no-search-res-text $tournamentID' style='display: none'>Kein Team gefunden!</div>";


$teams = $dbcn->execute_query("SELECT *
                                        FROM teams
                                            JOIN teams_in_tournaments tit ON teams.OPL_ID = tit.OPL_ID_team
                                        WHERE teams.OPL_ID <> -1
                                            AND tit.OPL_ID_group IN (
                                                SELECT OPL_ID
                                                FROM tournaments
                                                WHERE eventType = 'group'
                                                    AND OPL_ID_parent IN (
                                                        SELECT OPL_ID
                                                        FROM tournaments
                                                        WHERE eventType='league'
                                                            AND OPL_ID_parent = ?
                                                    )
                                            )
                                        ORDER BY teams.name", [$tournamentID])->fetch_all(MYSQLI_ASSOC);

$local_img_path = "img/team_logos/";
$logo_filename = is_light_mode() ? "logo_light.webp" : "logo.webp";

foreach ($teams as $i_teams=>$team) {
	$currTeam = $team["name"];
	$currTeamID = $team["OPL_ID"];
	$currTeamGroupID = $team["OPL_ID_group"];
	$currTeamDivID = $groups[$team["OPL_ID_group"]]["OPL_ID_parent"];
	$currTeamImgID = $team["OPL_ID_logo"];

	$team_name_now = $dbcn->execute_query("SELECT name FROM team_name_history WHERE OPL_ID_team = ? AND (update_time < ? OR ? IS NULL) ORDER BY update_time DESC", [$currTeamID,$tournament["dateEnd"],$tournament["dateEnd"]])->fetch_column();

	$team_rank = "";

	if (array_key_exists($currTeamDivID, $leagues)) {
		$team_rank .= "Liga {$leagues[$currTeamDivID]["number"]}";
		if (array_key_exists($currTeamGroupID, $groups)) {
			if ($groups[$currTeamGroupID] == 0) {
				$team_rank .= "";
			} else {
				$team_rank .= " Gruppe {$groups[$currTeamGroupID]["number"]}";
			}
		}
	}


	if ($currTeamImgID == NULL || !file_exists("../$local_img_path{$currTeamImgID}/logo.webp")) {
		$currTeamImgID = "";
		$img_url = "";
	} else {
		$img_url = $local_img_path . $currTeamImgID ."/". $logo_filename;
	}


	if (isset($filteredDivID) && $filteredDivID != $currTeamDivID) {
		$filterDClass = " filterD-off";
	} else {
		$filterDClass = "";
	}
	if (isset($filteredGroupID) && $filteredGroupID != $currTeamGroupID) {
		$filterGClass = " filterG-off";
	} else {
		$filterGClass = "";
	}

	echo "
                <div class=\"team-button $tournamentID $currTeamID $currTeamGroupID $currTeamDivID$filterDClass$filterGClass\" onclick='popup_team(\"$currTeamID\",\"$tournamentID\")'>
                    <div class='team-name'>";
	if ($img_url != NULL) {
		echo "
                        <img class='color-switch' alt src='$img_url'>";
	}
	echo "
                        <span>$team_name_now</span>
                    </div>
                    <div class='team-group'>
                        $team_rank
                    </div>
                </div>";
}
echo "</div>"; //Team-List

?>

</body>
</html>