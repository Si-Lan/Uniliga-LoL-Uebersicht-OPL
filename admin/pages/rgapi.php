<?php
$root = __DIR__."/../../";
include_once $root."setup/data.php";
include_once $root."functions/helper.php";
include_once $root."functions/fe-functions.php";

check_login();
?>
<!DOCTYPE html>
<html lang="de">
<?php

$dbcn = create_dbcn();
$loggedin = is_logged_in();
$lightmode = is_light_mode(true);

echo create_html_head_elements(css: ["rgapi2"], js: ["rgapi"], title: "Riot-API-Daten | Uniliga LoL - Übersicht" ,loggedin: $loggedin);

?>
<body class="admin <?php echo $lightmode?>">
<?php

echo create_header($dbcn, title: "rgapi", open_login: !$loggedin);

if ($loggedin) {
	$tournaments = $dbcn->query("SELECT * FROM tournaments WHERE eventType = 'tournament' ORDER BY OPL_ID DESC")->fetch_all(MYSQLI_ASSOC);
	echo "<main>";
	echo "<div class='slct'><select onchange='change_tournament(this.value)'>";
	foreach ($tournaments as $tournament) {
		echo "<option value='".$tournament['OPL_ID']."'>{$tournament["name"]}</option>";
	}
	echo "</select><span class='material-symbol'>".file_get_contents(__DIR__."/../../icons/material/arrow_drop_down.svg")."</span></div>";
	foreach ($tournaments as $index=>$tournament) {
		if ($index == 0) {
			$hiddenclass = "";
		} else {
			$hiddenclass = " hidden";
		}
		echo "<div class='writing-wrapper ".$tournament['OPL_ID'].$hiddenclass."'>";
		echo "<h2>{$tournament["name"]}</h2>";
        if ($tournament["archived"]) {
            echo "<span style='color: #ff6161; text-align: center'>Dieses Turnier ist archiviert!</span>";
        }
		echo "<button class='write puuids {$tournament['OPL_ID']}' onclick='get_puuids(\"{$tournament['OPL_ID']}\")'>get PUUIDs for Players without ID</button>";
		echo "<button class='write puuids-all {$tournament['OPL_ID']}' onclick='get_puuids(\"{$tournament['OPL_ID']}\",false)'>get PUUIDs for all Players</button>";
		echo "<button class='write riotids-puuids {$tournament['OPL_ID']}' onclick='get_riotids_by_puuids(\"{$tournament['OPL_ID']}\")'>get RiotIDs for all Players</button>";
		echo "<button class='write get-ranks {$tournament['OPL_ID']}' onclick='get_ranks(\"{$tournament['OPL_ID']}\")'>get Ranks for Players</button>";
		echo "<button class='write calc-team-rank {$tournament['OPL_ID']}' onclick='get_average_team_ranks(\"{$tournament['OPL_ID']}\")'>calculate average Ranks for Teams</button>";
		echo "<button class='deprecated-admin-btn write games {$tournament['OPL_ID']}' onclick=''>get all Games (API-Calls)</button>";
		echo "<button class='write gamedata {$tournament['OPL_ID']}' onclick='get_game_data(\"{$tournament['OPL_ID']}\")'>get Gamedata for Games without Data</button>";
		echo "<button class='deprecated-admin-btn write assign-una {$tournament['OPL_ID']}' onclick='assign_and_filter_games(\"{$tournament['OPL_ID']}\")'>sort all unsorted Games</button>";
		echo "<button class='deprecated-admin-btn write assign-all {$tournament['OPL_ID']}' onclick='assign_and_filter_games(\"{$tournament['OPL_ID']}\",0,1)'>sort all Games</button>";
		echo "<button class='write get-pstats {$tournament['OPL_ID']}' onclick='get_stats_for_players(\"{$tournament['OPL_ID']}\")'>calculate Playerstats</button>";
		echo "<button class='write teamstats {$tournament['OPL_ID']}' onclick='get_teamstats(\"{$tournament['OPL_ID']}\")'>calculate Teamstats</button>";
		echo "<div class='result-wrapper no-res {$tournament['OPL_ID']}'>
                        <div class='clear-button' onclick='clear_results(\"{$tournament['OPL_ID']}\")'>Clear</div>
                        <div class='result-content'></div>
                      </div>";
		echo "</div>";
	}
	echo "</main>";
}
?>
</body>
</html>
