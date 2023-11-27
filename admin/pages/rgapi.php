<!DOCTYPE html>
<html lang="de">
<?php
$root = __DIR__."/../../";
include_once $root."setup/data.php";
include_once $root."functions/helper.php";
include_once $root."functions/fe-functions.php";

$dbcn = create_dbcn();
$loggedin = is_logged_in();
$lightmode = is_light_mode(true);

echo create_html_head_elements(css: ["rgapi"], js: ["rgapi"], title: "Riot-API-Daten | Uniliga LoL - Übersicht" ,loggedin: $loggedin);

?>
<body class="admin <?php echo $lightmode?>">
<?php

echo create_header($dbcn, title: "rgapi", open_login: !$loggedin);

if ($loggedin) {
	$tournaments = $dbcn->query("SELECT * FROM tournaments WHERE eventType = 'tournament' ORDER BY OPL_ID DESC")->fetch_all(MYSQLI_ASSOC);
	echo "<div class='main-content'>";
	echo "<select onchange='change_tournament(this.value)'>";
	foreach ($tournaments as $tournament) {
		echo "<option value='".$tournament['OPL_ID']."'>{$tournament["name"]}</option>";
	}
	echo "</select>";
	foreach ($tournaments as $index=>$tournament) {
		if ($index == 0) {
			$hiddenclass = "";
		} else {
			$hiddenclass = " hidden";
		}
		echo "<div class='writing-wrapper ".$tournament['OPL_ID'].$hiddenclass."'>";
		echo "<h2>{$tournament["name"]}</h2>";
		echo "<a class='button write puuids {$tournament['OPL_ID']}' onclick='get_puuids(\"{$tournament['OPL_ID']}\")'>get PUUIDs for Players without ID</a>";
		echo "<a class='button write puuids-all {$tournament['OPL_ID']}' onclick='get_puuids(\"{$tournament['OPL_ID']}\",false)'>get PUUIDs for all Players</a>";
		echo "<a class='button write riotids-puuids {$tournament['OPL_ID']}' onclick='get_riotids_by_puuids(\"{$tournament['OPL_ID']}\")'>get RiotIDs for all Players</a>";
		echo "<a class='button write get-ranks {$tournament['OPL_ID']}' onclick='get_ranks(\"{$tournament['OPL_ID']}\")'>get Ranks for Players</a>";
		echo "<a class='button write calc-team-rank {$tournament['OPL_ID']}' onclick='get_average_team_ranks(\"{$tournament['OPL_ID']}\")'>calculate average Ranks for Teams</a>";
		echo "<a class='button write games {$tournament['OPL_ID']}' onclick='' style='color: #ff6161'>get all Games (API-Calls)</a>";
		echo "<a class='button write gamedata {$tournament['OPL_ID']}' onclick='get_game_data(\"{$tournament['OPL_ID']}\")'>get Gamedata for Games without Data</a>";
		echo "<a class='button write assign-una {$tournament['OPL_ID']}' onclick='assign_and_filter_games(\"{$tournament['OPL_ID']}\")'>sort all unsorted Games</a>";
		echo "<a class='button write assign-all {$tournament['OPL_ID']}' onclick='assign_and_filter_games(\"{$tournament['OPL_ID']}\",0,1)'>sort all Games</a>";
		echo "<a class='button write get-pstats {$tournament['OPL_ID']}' onclick='get_stats_for_players(\"{$tournament['OPL_ID']}\")'>calculate Playerstats</a>";
		echo "<a class='button write teamstats {$tournament['OPL_ID']}' onclick='get_teamstats(\"{$tournament['OPL_ID']}\")'>calculate Teamstats</a>";
		echo "<div class='result-wrapper no-res {$tournament['OPL_ID']}'>
                        <div class='clear-button' onclick='clear_results(\"{$tournament['OPL_ID']}\")'>Clear</div>
                        <div class='result-content'></div>
                      </div>";
		echo "</div>";
	}
	echo "</div>";
}
?>
</body>
</html>
