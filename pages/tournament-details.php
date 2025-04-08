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

$t_name_clean = preg_replace("/LoL\s/i","",$tournament["name"]);
echo create_html_head_elements(js: ["rgapi"], title: "$t_name_clean | Uniliga LoL - Übersicht", loggedin: $logged_in);

?>
<body class="tournament <?php echo $lightmode?> <?php echo $admin_btns;?>">
<?php

echo create_header($dbcn, title: "tournament", tournament_id: $tournamentID);

echo create_tournament_nav_buttons(tournament_id: $tournament_url_path, dbcn: $dbcn, active: "overview");

$leagues = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType='league' AND deactivated = FALSE ORDER BY number", [$tournamentID])->fetch_all(MYSQLI_ASSOC);
$wildcards = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType='wildcard' AND deactivated = FALSE", [$tournamentID])->fetch_all(MYSQLI_ASSOC);
$playoffs = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType='playoffs' AND deactivated = FALSE", [$tournamentID])->fetch_all(MYSQLI_ASSOC);
?>
<main>
    <h2 class='pagetitle'>Turnier-Details</h2>
<?php
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
?>
    <div class='divisions-list-wrapper'>
        <div class='divisions-list groups'>
    <?php
foreach ($leagues as $league) {
    if ($league["format"] == "swiss") {
        ?>
        <div class='division'>
            <div class='group-title-wrapper'>
                <h3>Liga <?php echo $league['number'] ?></h3>
            </div>
            <div class='groups'>
                <div class='group'>
                    <span class="group-title">Gruppe</span>
                    <div class="divider-vert-acc"></div>
                    <a href='turnier/<?php echo $tournament_url_path ?>/gruppe/<?php echo $league['OPL_ID'] ?>'
                       class='page-link'>
                        <span class="link-text">Details</span>
                        <span class="material-symbol page-link-icon"><?php echo file_get_contents("../icons/material/chevron_right.svg") ?></span>
                    </a>
                    <div class="divider-vert-acc"></div>
                    <a href='turnier/<?php echo $tournament_url_path ?>/teams?liga=<?php echo $league['OPL_ID'] ?>'
                       class='icon-link page-link'>
                        <span class='material-symbol icon-link-icon'><?php echo file_get_contents("../icons/material/group.svg") ?></span>
                        <span class="link-text">Teams</span>
                        <span class='material-symbol page-link-icon'><?php echo file_get_contents("../icons/material/chevron_right.svg") ?></span>
                    </a>
                </div>
            </div>
        </div>
                <?php
        continue;
    }

	$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType = 'group' AND OPL_ID_parent = ? ORDER BY number", [$league["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
    ?>
    <div class='division'>
        <div class='group-title-wrapper'>
            <h3>Liga <?php echo $league['number'] ?></h3>
        </div>
        <div class="groups">
    <?php
	foreach ($groups as $g_i=>$group) {
		$group_title = "Gruppe {$group['number']}";
        if ($g_i != 0) {
            ?> <div class="divider-light"></div> <?php
        }
        ?>
        <div class="group">
            <span class="group-title"><?php echo $group_title ?></span>
            <div class="divider-vert-acc"></div>
            <a href='turnier/<?php echo $tournament_url_path ?>/gruppe/<?php echo $group['OPL_ID'] ?>'
               class='page-link'>
                <span class="link-text">Details</span>
                <span class="material-symbol page-link-icon"><?php echo file_get_contents("../icons/material/chevron_right.svg") ?></span>
            </a>
            <div class="divider-vert-acc"></div>
            <a href='turnier/<?php echo $tournament_url_path ?>/teams?liga=<?php echo $league['OPL_ID'] ?>&gruppe=<?php echo $group['OPL_ID'] ?>'
               class='icon-link page-link'>
                <span class='material-symbol icon-link-icon'><?php echo file_get_contents("../icons/material/group.svg") ?></span>
                <span class="link-text">Teams</span>
                <span class='material-symbol page-link-icon'><?php echo file_get_contents("../icons/material/chevron_right.svg") ?></span>
            </a>
        </div>
                    <?php
	}
    ?>
        </div>
    </div>
                <?php
}
?>
        </div>
        <div class='divisions-list wildcard'<?php if (!$wildcard_active) echo " style='display: none'"; ?>>
            <div class='division'>
                <div class='group-title-wrapper'>
                    <h3>Wildcard</h3>
                </div>
                <div class="groups">
					<?php
					foreach ($wildcards as $i=>$wildcard) {
						$group_title = ($wildcard["numberRangeTo"] == null) ? "Wildcard Liga {$wildcard['number']}" : "Wildcard Liga {$wildcard['number']}-{$wildcard["numberRangeTo"]}";
						if ($i != 0) {
							?> <div class="divider-light"></div> <?php
						}
                        ?>
                        <div class="group">
                            <span class="group-title"><?php echo $group_title ?></span>
                            <div class="divider-vert-acc"></div>
                            <a href='turnier/<?php echo $tournament_url_path ?>/wildcard/<?php echo $wildcard['OPL_ID'] ?>'
                               class='page-link'>
                                <span class="link-text">Details</span>
                                <span class="material-symbol page-link-icon"><?php echo file_get_contents("../icons/material/chevron_right.svg") ?></span>
                            </a>
                            <div class="divider-vert-acc"></div>
                            <a href='turnier/<?php echo $tournament_url_path ?>/teams?liga=<?php echo $wildcard['OPL_ID'] ?>'
                               class='icon-link page-link'>
                                <span class='material-symbol icon-link-icon'><?php echo file_get_contents("../icons/material/group.svg") ?></span>
                                <span class="link-text">Teams</span>
                                <span class='material-symbol page-link-icon'><?php echo file_get_contents("../icons/material/chevron_right.svg") ?></span>
                            </a>
                        </div>
                    <?php
					}
					?>
                </div>
            </div>
        </div>
        <div class='divisions-list playoffs'<?php if (!$playoffs_active) echo " style='display: none'"; ?>>
            <div class='division'>
                <div class='group-title-wrapper'>
                    <h3>Playoffs</h3>
                </div>
                <div class="groups">
					<?php
					foreach ($playoffs as $i=>$playoff) {
						$group_title = ($playoff["numberRangeTo"] == null) ? "Playoffs Liga {$playoff['number']}" : "Playoffs Liga {$playoff['number']}/{$playoff["numberRangeTo"]}";
						if ($i != 0) {
							?> <div class="divider-light"></div> <?php
						}
						?>
                        <div class="group">
                            <span class="group-title"><?php echo $group_title ?></span>
                            <div class="divider-vert-acc"></div>
                            <a href='turnier/<?php echo $tournament_url_path ?>/playoffs/<?php echo $playoff['OPL_ID'] ?>'
                               class='page-link'>
                                <span class="link-text">Details</span>
                                <span class="material-symbol page-link-icon"><?php echo file_get_contents("../icons/material/chevron_right.svg") ?></span>
                            </a>
                            <div class="divider-vert-acc"></div>
                            <a href='turnier/<?php echo $tournament_url_path ?>/teams?liga=<?php echo $playoff['OPL_ID'] ?>'
                               class='icon-link page-link'>
                                <span class='material-symbol icon-link-icon'><?php echo file_get_contents("../icons/material/group.svg") ?></span>
                                <span class="link-text">Teams</span>
                                <span class='material-symbol page-link-icon'><?php echo file_get_contents("../icons/material/chevron_right.svg") ?></span>
                            </a>
                        </div>
						<?php
					}
					?>
                </div>
            </div>
        </div>
    </div>
</main>
<?php
$dbcn->close();
?>
</body>
</html>