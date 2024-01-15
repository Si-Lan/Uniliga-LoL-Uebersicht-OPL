<!DOCTYPE html>
<html lang="de">
<?php
include_once __DIR__."/../setup/data.php";
include_once __DIR__."/../functions/fe-functions.php";
include_once __DIR__."/../functions/summoner-card.php";

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
$teamID = $_GET["team"] ?? NULL;

$tournamentID = $tournament_url_path;
if (preg_match("/^(winter|sommer)([0-9]{2})$/",strtolower($tournamentID),$url_path_matches)) {
	$split = $url_path_matches[1];
	$season = $url_path_matches[2];
	$tournamentID = $dbcn->execute_query("SELECT OPL_ID FROM tournaments WHERE season = ? AND split = ? AND eventType = 'tournament'", [$season, $split])->fetch_column();
}
$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ? AND eventType = 'tournament'", [$tournamentID])->fetch_assoc();
$team_groups = $dbcn->execute_query("SELECT * FROM teams JOIN teams_in_tournaments tit ON teams.OPL_ID = tit.OPL_ID_team WHERE OPL_ID = ? AND OPL_ID_group IN (SELECT OPL_ID FROM tournaments WHERE eventType='group' AND OPL_ID_parent IN (SELECT OPL_ID FROM tournaments WHERE eventType='league' AND OPL_ID_parent = ?))", [$teamID, $tournamentID])->fetch_all(MYSQLI_ASSOC);
$team_playoffs = $dbcn->execute_query("SELECT * FROM teams JOIN teams_in_tournaments tit ON teams.OPL_ID = tit.OPL_ID_team WHERE OPL_ID = ? AND OPL_ID_group IN (SELECT OPL_ID FROM tournaments WHERE eventType='playoffs' AND OPL_ID_parent = ?)", [$teamID, $tournamentID])->fetch_all(MYSQLI_ASSOC);
$team_solo = $dbcn->execute_query("SELECT * FROM teams WHERE OPL_ID = ?", [$teamID])->fetch_assoc();
$team_rank = $dbcn->execute_query("SELECT tsr.* FROM teams t LEFT JOIN teams_season_rank tsr ON tsr.OPL_ID_team = t.OPL_ID AND tsr.season = (SELECT tr.season FROM tournaments as tr WHERE tr.OPL_ID = ?) WHERE t.OPL_ID = ?", [$tournamentID, $teamID])->fetch_assoc();

if ($tournament == NULL) {
	echo create_html_head_elements(title: "Turnier nicht gefunden | Uniliga LoL - Übersicht");
	echo "<body class='$lightmode'>";
	echo create_header(title: "error");
	echo "<div style='text-align: center'>Kein Turnier unter der angegebenen ID gefunden!</div></body>";
	exit();
}
if ($team_groups == NULL && $team_solo != NULL) {
	echo create_html_head_elements(title: "Team nicht im Turnier | Uniliga LoL - Übersicht");
	echo "<body class='$lightmode'>";
	echo create_header(title: "error");
	echo "<div style='text-align: center'>Dieses Team spielt nicht im angegebenen Turnier!</div><div style='display: flex; flex-direction: column; align-items: center;'><a class='button' href='team/$teamID'>zur Team-Seite</a></div></body>";
	exit();
}
if ($team_groups == NULL && $team_solo == NULL) {
	echo create_html_head_elements(title: "Team nicht gefunden | Uniliga LoL - Übersicht");
	echo "<body class='$lightmode'>";
	echo create_header(title: "error");
	echo "<div style='text-align: center'>Kein Team unter der angegebenen ID gefunden!</div></body>";
	exit();
}

// TODO: möglichkeit zwischen gruppen zu wechseln hinzufügen
if (count($team_groups) > 1) {
	// TODO: prüfen welche gruppe initial aufgerufen wurde
	$team = $team_groups[0];
} else {
	$team = $team_groups[0];
}
if (count($team_playoffs) == 0) {
    $playoff_ID = null;
} else {
    // TODO: mehrere Playoffs anzeigen können
    $playoff_ID = $team_playoffs[0]["OPL_ID_group"];
}

$group = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType='group' AND OPL_ID = ?", [$team["OPL_ID_group"]])->fetch_assoc();
$league = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType='league' AND OPL_ID = ?", [$group["OPL_ID_parent"]])->fetch_assoc();
$playoff = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType='playoffs' AND OPL_ID = ?", [$playoff_ID])->fetch_assoc();

$t_name_clean = preg_replace("/LoL/","",$tournament["name"]);
echo create_html_head_elements(css: ["game"], js: ["rgapi"], title: "{$team["name"]} | $t_name_clean", loggedin: $logged_in);

$open_popup = "";
if (isset($_GET['match'])) {
	$open_popup = "popup_open";
}

?>
<body class="team <?php echo "$lightmode $open_popup $admin_btns"?>">
<?php

$pageurl = $_SERVER['REQUEST_URI'];
$local_img_path = "img/team_logos/";
$opl_tourn_url = "https://www.opleague.pro/event/";
$opgg_logo_svg = file_get_contents(__DIR__."/../img/opgglogo.svg");
$opgg_url = "https://www.op.gg/multisearch/euw?summoners=";

$players = $dbcn->execute_query("SELECT * FROM players JOIN players_in_teams_in_tournament pit on players.OPL_ID = pit.OPL_ID_player AND pit.OPL_ID_tournament = ? LEFT JOIN stats_players_teams_tournaments spit ON pit.OPL_ID_player = spit.OPL_ID_player AND spit.OPL_ID_team = pit.OPL_ID_team AND spit.OPL_ID_tournament = ? WHERE pit.OPL_ID_team = ? ", [$tournamentID, $tournamentID, $teamID])->fetch_all(MYSQLI_ASSOC);
$matches = $dbcn->execute_query("SELECT * FROM matchups WHERE OPL_ID_tournament = ? AND (OPL_ID_team1 = ? OR OPL_ID_team2 = ?)", [$group["OPL_ID"],$teamID,$teamID])->fetch_all(MYSQLI_ASSOC);
$matches_playoffs = $dbcn->execute_query("SELECT * FROM matchups WHERE OPL_ID_tournament = ? AND (OPL_ID_team1 = ? OR OPL_ID_team2 = ?)", [$playoff_ID,$teamID,$teamID])->fetch_all(MYSQLI_ASSOC);

$opgglink = $opgg_url;
for ($i = 0; $i < count($players); $i++) {
	if ($i != 0) {
		$opgglink .= urlencode(",");
	}
	$opgglink .= urlencode($players[$i]["riotID_name"]."#".$players[$i]["riotID_tag"]);
}
$player_amount = count($players);
//$players_by_id = array();
$players_gamecount_by_id = array();
foreach ($players as $player) {
	//$players_by_id[$player['OPL_ID']] = $player;
	$played_games = 0;
	if ($player['roles'] == NULL) {
		$players_gamecount_by_id[$player['OPL_ID']] = $played_games;
		continue;
	}
	foreach (json_decode($player['roles'],true) as $role_played_amount) {
		$played_games += $role_played_amount;
	}
	$players_gamecount_by_id[$player['OPL_ID']] = $played_games;
}
arsort($players_gamecount_by_id);

$last_user_update = $dbcn->execute_query("SELECT last_update FROM updates_user_team WHERE OPL_ID_team = ?", [$teamID])->fetch_column();
$last_cron_update = $dbcn->execute_query("SELECT last_update FROM updates_cron WHERE OPL_ID_tournament = ?", [$tournamentID])->fetch_column();

$last_update = max($last_user_update,$last_cron_update);

if ($last_update == NULL) {
	$updatediff = "unbekannt";
} else {
	$last_update = strtotime($last_update);
	$currtime = time();
	$updatediff = max_time_from_timestamp($currtime-$last_update);
}

echo create_header(dbcn: $dbcn, title: "tournament", tournament_id: $tournamentID);

echo create_tournament_nav_buttons($tournamentID, $dbcn,"",$league['OPL_ID'],$group["OPL_ID"]);

echo create_team_nav_buttons($tournamentID,$group["OPL_ID"],$team,"details",playoffID: $playoff_ID,updatediff: $updatediff);

echo "<div class='main-content'>";
echo "
                <div class='player-cards opgg-cards'>
                    <div class='title'>
                        <h3>Spieler</h3>
                        <a href='$opgglink' class='button op-gg' target='_blank'><div class='svg-wrapper op-gg'>$opgg_logo_svg</div><span class='player-amount'>({$player_amount} Spieler)</span></a>";
$collapsed = summonercards_collapsed();
if ($collapsed) {
	echo "<button type='button' class='exp_coll_sc'><div class='material-symbol'>".file_get_contents(__DIR__."/../icons/material/unfold_more.svg")."</div>Stats ein</button>";
} else {
	echo "<button type='button' class='exp_coll_sc'><div class='material-symbol'>".file_get_contents(__DIR__."/../icons/material/unfold_less.svg")."</div>Stats aus</button>";
}
echo "
                     </div>";
if ($team_rank['avg_rank_tier'] != NULL) {
	$avg_rank = strtolower($team_rank['avg_rank_tier']);
	$avg_rank_cap = ucfirst($avg_rank);
	echo "
                    <div class='team-avg-rank'>
                        Team-Rang: 
                        <img class='rank-emblem-mini' src='ddragon/img/ranks/mini-crests/{$avg_rank}.svg' alt='$avg_rank_cap'>
                        <span>{$avg_rank_cap} {$team_rank['avg_rank_div']}</span>
                    </div>";
}
echo "
                    <div class='summoner-card-container'>";
foreach ($players_gamecount_by_id as $playerID=>$player_gamecount) {
	echo create_summonercard($dbcn,$playerID,$tournamentID,$teamID,$collapsed);
}
echo "
                    </div> 
                </div>"; //summoner-card-container -then- player-cards

echo "<div class='inner-content'>";

echo create_standings($dbcn,$tournamentID,$group['OPL_ID'],$teamID);

echo "<div class='matches'>
                     <div class='title'><h3>Spiele</h3></div>";

$curr_matchID = $_GET['match'] ?? NULL;
if ($curr_matchID != NULL) {
	$curr_matchData = $dbcn->execute_query("SELECT * FROM matchups WHERE OPL_ID = ?",[$curr_matchID])->fetch_assoc();
	$curr_games = $dbcn->execute_query("SELECT * FROM games g JOIN games_to_matches gtm on g.RIOT_matchID = gtm.RIOT_matchID WHERE OPL_ID_matches = ? ORDER BY g.RIOT_matchID",[$curr_matchID])->fetch_all(MYSQLI_ASSOC);
	$curr_team1 = $dbcn->execute_query("SELECT * FROM teams WHERE OPL_ID = ?",[$curr_matchData['OPL_ID_team1']])->fetch_assoc();
	$curr_team2 = $dbcn->execute_query("SELECT * FROM teams WHERE OPL_ID = ?",[$curr_matchData['OPL_ID_team2']])->fetch_assoc();

	$last_user_update_match = $dbcn->execute_query("SELECT last_update FROM updates_user_matchup WHERE OPL_ID_matchup = ?", [$curr_matchID])->fetch_column();

	$last_update_match = max($last_user_update_match,$last_cron_update);

	if ($last_update_match == NULL) {
		$updatediff_match = "unbekannt";
	} else {
		$last_update_match = strtotime($last_update_match);
		$currtime = time();
		$updatediff_match = max_time_from_timestamp($currtime-$last_update_match);
	}

	echo "
                    <div class='mh-popup-bg' onclick='close_popup_match(event)' style='display: block; opacity: 1;'>
                        <div class='mh-popup'>
                            <div class='close-button' onclick='closex_popup_match()'><div class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/close.svg") ."</div></div>
                            <div class='close-button-space'></div>
                            <div class='mh-popup-buttons'>
	                            <a class='button' href='turnier/$tournamentID/team/$teamID/matchhistory#{$curr_matchID}'><div class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/manage_search.svg") ."</div>in Matchhistory ansehen</a>
	                            <div class='updatebuttonwrapper'><button type='button' class='icononly user_update_match update_data' data-match='$curr_matchID' data-matchformat='' data-team='$teamID' data-group='{$group["OPL_ID"]}'><div class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/sync.svg") ."</div></button><span>letztes Update:<br>$updatediff_match</span></div>
	                        </div>";
	if ($curr_matchData['winner'] == $curr_matchData['OPL_ID_team1']) {
		$team1score = "win";
		$team2score = "loss";
	} elseif ($curr_matchData['winner'] == $curr_matchData['OPL_ID_team2']) {
		$team1score = "loss";
		$team2score = "win";
	} else {
		$team1score = "draw";
		$team2score = "draw";
	}
	$t1score = $curr_matchData['team1Score'];
	$t2score = $curr_matchData['team2Score'];
	if ($t1score == -1 || $t2score == -1) {
		$t1score = ($t1score == -1) ? "L" : "W";
		$t2score = ($t2score == -1) ? "L" : "W";
	}
	echo "
                <h2 class='round-title'>
                    <span class='round'>Runde {$curr_matchData['playday']}: &nbsp</span>
                    <span class='team $team1score'>{$curr_team1['name']}</span>
                    <span class='score'><span class='$team1score'>{$t1score}</span>:<span class='$team2score'>{$t2score}</span></span>
                    <span class='team $team2score'>{$curr_team2['name']}</span>
                </h2>";
	foreach ($curr_games as $game_i=>$curr_game) {
		echo "<div class='game game$game_i'>";
		$gameID = $curr_game['RIOT_matchID'];
		echo create_game($dbcn,$gameID,$teamID);
		echo "</div>";
	}
	echo "
                        </div>
                    </div>";
} else {
	echo "   <div class='mh-popup-bg' onclick='close_popup_match(event)'>
                            <div class='mh-popup'></div>
                     </div>";
}


echo "<div class='match-content content'>";
foreach ($matches as $match) {
	echo create_matchbutton($dbcn,$match['OPL_ID'],"groups",$teamID);
}
if ($matches_playoffs != null && count($matches_playoffs) > 0) {
    echo "<h4>Playoffs</h4>";
}
foreach ($matches_playoffs as $match) {
	echo create_matchbutton($dbcn,$match['OPL_ID'],"playoffs",$teamID);
}
echo "</div>";
echo "</div>"; // matches
echo "</div>"; // inner-content
echo "</div>"; // main-content

if ($logged_in) {
	echo "<div class='writing-wrapper'>";
	echo "<div class='divider big-space'></div>";
	echo "<a class='button write games-team $teamID {$tournament['OPL_ID']}' onclick='get_games_for_team(\"{$tournament['OPL_ID']}\",\"$teamID\")'>Lade Spiele für {$team['name']}</a>";
	echo "<a class='button write gamedata {$tournament['OPL_ID']}' onclick='get_game_data(\"{$tournament['OPL_ID']}\",\"$teamID\")'>Lade Spiel-Daten für geladene Spiele</a>";
	echo "<a class='button write assign-una {$tournament['OPL_ID']}' onclick='assign_and_filter_games(\"{$tournament['OPL_ID']}\",\"$teamID\")'>sortiere unsortierte Spiele</a>";
	echo "<div class='result-wrapper no-res $teamID {$tournament['OPL_ID']}'>
                        <div class='clear-button' onclick='clear_results(\"$teamID\")'>Clear</div>
                        <div class='result-content'></div>
                      </div>";
	echo "</div>"; // writing-wrapper
}

?>
</body>
</html>