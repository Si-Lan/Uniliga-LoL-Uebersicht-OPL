<?php
include_once __DIR__."/../setup/data.php";
include_once __DIR__."/../functions/fe-functions.php";
check_login();
?>
<!DOCTYPE html>
<html lang="de">
<?php

$lightmode = is_light_mode(true);
$logged_in = is_logged_in();
$admin_btns = admin_buttons_visible(true);

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
echo create_html_head_elements(js: ["rgapi"], title: "$t_name_clean | Uniliga LoL - Übersicht", loggedin: $logged_in);

?>
<body class="tournament <?php echo $lightmode?> <?php echo $admin_btns;?>">
<?php

echo create_header($dbcn, title: "tournament", tournament_id: $tournamentID);

echo create_tournament_nav_buttons(tournament_id: $tournament_url_path, dbcn: $dbcn, active: "overview");

$leagues = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType='league' AND deactivated = FALSE ORDER BY number", [$tournamentID])->fetch_all(MYSQLI_ASSOC);
$wildcards = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType='wildcard' AND deactivated = FALSE", [$tournamentID])->fetch_all(MYSQLI_ASSOC);
$playoffs = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType='playoffs' AND deactivated = FALSE", [$tournamentID])->fetch_all(MYSQLI_ASSOC);

echo "<h2 class='pagetitle'>Turnier-Details</h2>";

$groups_active = (count($leagues)>0) ? "active" : "";
$wildcard_active = (count($wildcards)>0 && count($leagues)==0) ? "active" : "";
$playoffs_active = (count($wildcards)==0 && count($leagues)==0) ? "active" : "";
if (count($leagues) > 0 ? (count($wildcards) > 0 || count($playoffs) > 0) : (count($wildcards) > 0 && count($playoffs) > 0)) {
    ?>
    <div id="tournamentpage_switch_stage_buttons">
        <?php
        if (count($wildcards) > 0) {
            ?>
            <button type="button" class="tournamentpage_switch_stage <?php echo $wildcard_active?>" data-stage="wildcard">Wildcard-Turnier</button>
            <?php
		}
		if (count($leagues) > 0) {
			?>
            <button type="button" class="tournamentpage_switch_stage <?php echo $groups_active?>" data-stage="groups">Gruppenphase</button>
			<?php
		}
		if (count($playoffs) > 0) {
			?>
            <button type="button" class="tournamentpage_switch_stage <?php echo $playoffs_active?>" data-stage="playoffs">Playoffs</button>
			<?php
		}
        ?>
    </div>
    <?php
}

echo "<div class='divisions-list-wrapper'>";

echo "<div class='divisions-list groups'>";
foreach ($leagues as $league) {
	echo "<div class='division'>
                        <div class='group-title-wrapper'><h2>Liga {$league['number']}</h2>";
	if ($logged_in) {
		echo "<a class='deprecated-admin-btn button write games-div {$league['OPL_ID']}' onclick='get_games_for_division(\"$tournamentID\",\"{$league['OPL_ID']}\")'><div class='material-symbol'>". file_get_contents("../icons/material/place_item.svg") ."</div>Lade Spiele</a>";
	}
	echo "</div>";
	if ($logged_in) {
		echo "<div class='result-wrapper no-res {$league['OPL_ID']} {$tournamentID}'>
                            <div class='clear-button' onclick='clear_results(\"{$league['OPL_ID']}\")'>Clear</div>
                            <div class='result-content'></div>
                          </div>";
	}
	echo "<div class='divider'></div>";

    if ($league["format"] == "swiss") {
        echo "<div class='groups'>";
		echo "<div>";
		echo "<div class='group'>
                            <a href='turnier/{$tournament_url_path}/gruppe/{$league['OPL_ID']}' class='button'>Swiss-Gruppe</a>
                            <a href='turnier/{$tournament_url_path}/teams?liga={$league['OPL_ID']}' class='button'><div class='material-symbol'>". file_get_contents("../icons/material/group.svg") ."</div>Teams</a>";
		echo "</div>"; // group
		echo "</div>"; // <div>
		echo "</div>"; // groups
		echo "</div>"; // division
        continue;
    }

	$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType = 'group' AND OPL_ID_parent = ? ORDER BY number", [$league["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);

	echo "<div class='groups'>";
	foreach ($groups as $group) {
		$group_title = "Gruppe {$group['number']}";

		echo "<div>";
		echo "<div class='group'>
                            <a href='turnier/{$tournament_url_path}/gruppe/{$group['OPL_ID']}' class='button'>$group_title</a>
                            <a href='turnier/{$tournament_url_path}/teams?liga={$league['OPL_ID']}&gruppe={$group['OPL_ID']}' class='button'><div class='material-symbol'>". file_get_contents("../icons/material/group.svg") ."</div>Teams</a>";
		echo "</div>"; // group
		if ($logged_in) {
			echo "<a class='deprecated-admin-btn button write games- {$group['OPL_ID']}' onclick='get_games_for_group(\"$tournamentID\",\"{$group['OPL_ID']}\")'><div class='material-symbol'>". file_get_contents("../icons/material/place_item.svg") ."</div>Lade Spiele</a>";
		}
		echo "</div>";
		if ($logged_in) {
			echo "
                            <div class='result-wrapper no-res {$group['OPL_ID']} {$tournamentID}'>
                                <div class='clear-button' onclick='clear_results(\"{$group['OPL_ID']}\")'>Clear</div>
                                <div class='result-content'></div>
                            </div>";
		}
	}
	echo "</div>";

	echo "</div>";
}
echo "</div>";


?>
<div class='divisions-list wildcard'<?php if (!$wildcard_active) echo " style='display: none'"; ?>>
<?php

echo "<div class='division'>
                        <div class='group-title-wrapper'><h2>Wildcard</h2>";
echo "</div>";
echo "<div class='divider'></div>";


echo "<div class='groups'>";
foreach ($wildcards as $wildcard) {
	$group_title = ($wildcard["numberRangeTo"] == null) ? "Wildcard Liga {$wildcard['number']}" : "Wildcard Liga {$wildcard['number']}-{$wildcard["numberRangeTo"]}";

	echo "<div>";
	echo "<div class='group'>
                            <a href='turnier/{$tournament_url_path}/wildcard/{$wildcard['OPL_ID']}' class='button'>$group_title</a>
                            <a href='turnier/{$tournament_url_path}/teams?liga={$wildcard['OPL_ID']}' class='button'><div class='material-symbol'>" . file_get_contents("../icons/material/group.svg") . "</div>Teams</a>";
	echo "</div>"; // group

	if ($logged_in) {
		echo "<a class='button write games- {$wildcard['OPL_ID']}' onclick='get_games_for_group(\"$tournamentID\",\"{$wildcard['OPL_ID']}\")'><div class='material-symbol'>" . file_get_contents("../icons/material/place_item.svg") . "</div>Lade Spiele</a>";
	}

	echo "</div>";

	if ($logged_in) {
		echo "
							<div class='result-wrapper no-res {$wildcard['OPL_ID']} {$tournamentID}'>
								<div class='clear-button' onclick='clear_results(\"{$wildcard['OPL_ID']}\")'>Clear</div>
								<div class='result-content'></div>
							</div>";
	}
}
echo "</div>"; //groups
echo "</div>"; //division
echo "</div>"; //divisions-list

echo "</div>"; //divisions-list-wrapper

$dbcn->close();
?>

</body>
</html>