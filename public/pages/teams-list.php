<?php
/** @var mysqli $dbcn  */

use App\UI\Page\PageMeta;

$tournament_url_path = $_GET["tournament"] ?? NULL;
$tournamentID = $tournament_url_path;
if (preg_match("/^(winter|sommer)([0-9]{2})$/",strtolower($tournamentID),$url_path_matches)) {
	$split = $url_path_matches[1];
	$season = $url_path_matches[2];
	$tournamentID = $dbcn->execute_query("SELECT OPL_ID FROM tournaments WHERE season = ? AND split = ? AND eventType = 'tournament'", [$season, $split])->fetch_column();
}

$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ? AND eventType = 'tournament'", [$tournamentID])->fetch_assoc();

if ($tournament == NULL) {
	$_GET["error"] = "404";
	$_GET["404type"] = "tournament";
	$_GET["tournamentid"] = $tournamentID;
	require "error.php";
	echo "</html>";
	exit();
}

$t_name_clean = preg_replace("/LoL\s/i","",$tournament["name"]);
$pageMeta = new PageMeta("Team-Liste - $t_name_clean", bodyClass: 'teamlist');

echo create_header($dbcn, title: "tournament", tournament_id: $tournamentID);

echo create_tournament_nav_buttons(tournament_id: $tournament_url_path, dbcn: $dbcn, active: "list");

$leaguesDB = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'league' AND deactivated = FALSE ORDER BY number", [$tournamentID])->fetch_all(MYSQLI_ASSOC);
$leagues = $groups = [];
foreach ($leaguesDB as $league) {
	$leagues[$league["OPL_ID"]] = $league;
	$groupsDB = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'group'", [$league["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
	foreach ($groupsDB as $group) {
		$groups[$group["OPL_ID"]] = $group;
	}
}
$wildcardsDB = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = 'wildcard'",[$tournamentID])->fetch_all(MYSQLI_ASSOC);
$wildcards = [];
foreach ($wildcardsDB as $wildcard) {
    $wildcards[$wildcard["OPL_ID"]] = $wildcard;
}
?>
<main>
    <h2 class="pagetitle">Team-Liste</h2>
    <div class="searchbar">
		<span class="material-symbol search-icon" title="Suche">
            <?php echo file_get_contents(dirname(__DIR__)."/icons/material/search.svg") ?>
		</span>
        <input class="search-teams deletable-search" onkeyup='search_teams()' placeholder="Teams durchsuchen" type="search">
        <button class="material-symbol search-clear" title="Suche leeren">
			<?php echo file_get_contents(dirname(__DIR__)."/icons/material/close.svg") ?>
        </button>
    </div>
	<?php

	if (isset($_GET["liga"]) && (array_key_exists($_GET["liga"],$leagues) || array_key_exists($_GET["liga"],$wildcards))) {
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
		$toGroupButtonLink = " href='/turnier/".$tournamentID."/gruppe/".$filteredGroupID."'";
	} elseif (isset($filteredDivID) && array_key_exists($filteredDivID,$leagues) && $leagues[$filteredDivID]["format"] == "swiss") {
		$groupallClass = "selected='selected'";
		$toGroupButtonClass = " shown";
		$toGroupButtonLink = " href='/turnier/".$tournamentID."/gruppe/".$filteredDivID."'";
	} else {
		$groupallClass = "selected='selected'";
	}
	?>

    <div class="team-filter-wrap">
        <h3>Filter</h3>
        <div class="slct div-select-wrap">
            <select name='Ligen' class='divisions' onchange='filter_teams_list_division(this.value)'>
                <option value='all' <?php echo $divallClass ?>>Alle Ligen</option>
                <?php
				foreach ($leagues as $league) {
					if (isset($filteredDivID) && $filteredDivID == $league['OPL_ID']) {
						$divClass = " selected='selected'";
					} else {
						$divClass = "";
					}
					if ($league["format"] == "swiss") {
						$swissClass = "swiss_league";
					} else {
						$swissClass = "";
					}
					echo "<option value='{$league["OPL_ID"]}'$divClass class='$swissClass'>Liga {$league["number"]}</option>";
				}
				foreach ($wildcards as $wildcard) {
					if (isset($filteredDivID) && $filteredDivID == $wildcard['OPL_ID']) {
						$divClass = " selected='selected'";
					} else {
						$divClass = "";
					}
					$wildcard_numbers_combined = ($wildcard["numberRangeTo"] == null) ? $wildcard["number"] : $wildcard["number"]."-".$wildcard["numberRangeTo"];
					echo "<option value='{$wildcard["OPL_ID"]}'$divClass class='wildcard_league'>Wildcard $wildcard_numbers_combined</option>";
				}
                ?>
            </select>
            <span class='material-symbol'><?php echo file_get_contents(dirname(__DIR__)."/icons/material/arrow_drop_down.svg") ?></span>
        </div>
        <div class='slct groups-select-wrap'>
            <select name='Gruppen' class='groups' onchange='filter_teams_list_group(this.value)'>
                <option value='all' <?php echo $groupallClass ?>>Alle Gruppen</option>
                <?php
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
                ?>
            </select>
            <span class='material-symbol'><?php echo file_get_contents(dirname(__DIR__)."/icons/material/arrow_drop_down.svg") ?></span>
        </div>
        <a class="button b-group page-link<?php echo $toGroupButtonClass?>"<?php echo $toGroupButtonLink?>>
            <span class="link-text">
                zur Gruppe
            </span>
            <span class="material-symbol page-link-icon">
                <?php echo file_get_contents(dirname(__DIR__)."/icons/material/chevron_right.svg") ?>
            </span>
        </a>
    </div>
<?php

echo "
            <div class='team-popup-bg' onclick='close_popup_team(event)'>
                <div class='team-popup'></div>
            </div>";
echo "<div class='team-list $tournamentID'>";
echo "<div class='no-search-res-text $tournamentID' style='display: none'>Kein Team gefunden!</div>";


$teams = $dbcn->execute_query("SELECT *
                                        FROM teams
                                            JOIN teams_in_tournaments tit ON teams.OPL_ID = tit.OPL_ID_team
                                        WHERE teams.OPL_ID > -1
                                            AND (tit.OPL_ID_group IN (
                                                SELECT OPL_ID
                                                FROM tournaments
                                                WHERE (eventType = 'group' OR (eventType = 'league' AND format = 'swiss'))
                                                    AND OPL_ID_top_parent = ?
                                                )
                                                OR (tit.OPL_ID_group IN (
                                                        SELECT OPL_ID
                                                        FROM tournaments
                                                        WHERE eventType='wildcard' AND OPL_ID_top_parent = ?
                                                        ) 
                                                    AND teams.OPL_ID NOT IN (
                                                        SELECT OPL_ID_team
                                                        FROM teams_in_tournaments tit JOIN tournaments t ON tit.OPL_ID_group = t.OPL_ID
                                                        WHERE (eventType = 'group' OR (eventType = 'league' AND format = 'swiss')) AND OPL_ID_top_parent = ?
                                                        )
                                                    )
                                                ) 
                                        ORDER BY teams.name", [$tournamentID,$tournamentID,$tournamentID])->fetch_all(MYSQLI_ASSOC);



$local_img_path = "/img/team_logos/";
$logo_filename = is_light_mode() ? "logo_light.webp" : "logo.webp";

foreach ($teams as $i_teams=>$team) {
	$currTeam = $team["name"];
	$currTeamID = $team["OPL_ID"];
	$currTeamGroupID = $team["OPL_ID_group"];

    $currTeamWildcards = $dbcn->execute_query("SELECT * FROM teams_in_tournaments tit JOIN tournaments t ON tit.OPL_ID_group = t.OPL_ID WHERE tit.OPL_ID_team = ? AND t.eventType='wildcard' AND t.OPL_ID_top_parent = ?", [$currTeamID, $tournamentID])->fetch_all(MYSQLI_ASSOC);

    $wildcardID_data = "";
    $wildcardIDs = [];
    foreach ($currTeamWildcards as $w_i=>$wildcard) {
        $wildcardID_data .= ($w_i == 0) ? "" : " ";
        $wildcardID_data .= $wildcard["OPL_ID_group"];
        $wildcardIDs[] = $wildcard["OPL_ID_group"];
    }

    if (array_key_exists($currTeamGroupID,$leagues) || array_key_exists($currTeamGroupID,$wildcards)) {
        $currTeamDivID = $currTeamGroupID;
    } else {
		$currTeamDivID = $groups[$team["OPL_ID_group"]]["OPL_ID_parent"];
	}
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
	} elseif (array_key_exists($currTeamGroupID,$wildcards)) {
		$wildcard_numbers_combined = ($wildcards[$currTeamGroupID]["numberRangeTo"] == null) ? $wildcards[$currTeamGroupID]["number"] : $wildcards[$currTeamGroupID]["number"]."-".$wildcards[$currTeamGroupID]["numberRangeTo"];
        $team_rank = "Wildcard-Turnier Liga ".$wildcard_numbers_combined;
    }


	if ($currTeamImgID == NULL || !file_exists(dirname(__DIR__)."/$local_img_path{$currTeamImgID}/logo.webp")) {
		$currTeamImgID = "";
		$img_url = "";
	} else {
		$img_url = $local_img_path . $currTeamImgID ."/". $logo_filename;
	}


	if (isset($filteredDivID) && $filteredDivID != $currTeamDivID && !in_array($filteredDivID, $wildcardIDs)) {
		$filterDClass = " filterD-off";
	} else {
		$filterDClass = "";
	}
	if (isset($filteredGroupID) && $filteredGroupID != $currTeamGroupID) {
		$filterGClass = " filterG-off";
	} else {
		$filterGClass = "";
	}

    ?>
    <button class="team-button <?php echo $filterDClass.$filterGClass ?>" data-league='<?php echo $currTeamDivID?>' data-group='<?php echo$currTeamGroupID?>' data-wildcards='<?php echo$wildcardID_data?>' onclick='popup_team(<?php echo $currTeamID?>,<?php echo $tournamentID?>)'>
        <?php
		if ($img_url != NULL) {
            ?><img class='color-switch' alt src='<?php echo $img_url?>'><?php
		}
        ?>
        <span>
            <span class="team-name"><?php echo $team_name_now?></span>
            <span class="team-group"><?php echo $team_rank?></span>
        </span>
    </button>
    <?php
}
echo "</div>"; //Team-List

?>
</main>