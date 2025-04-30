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
$teamID = $_GET["team"] ?? NULL;

$tournamentID = $tournament_url_path;
if (preg_match("/^(winter|sommer)([0-9]{2})$/",strtolower($tournamentID),$url_path_matches)) {
	$split = $url_path_matches[1];
	$season = $url_path_matches[2];
	$tournamentID = $dbcn->execute_query("SELECT OPL_ID FROM tournaments WHERE season = ? AND split = ? AND eventType = 'tournament'", [$season, $split])->fetch_column();
}
$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ? AND eventType = 'tournament'", [$tournamentID])->fetch_assoc();
$team_groups = $dbcn->execute_query("SELECT * FROM teams JOIN teams_in_tournaments tit ON teams.OPL_ID = tit.OPL_ID_team WHERE OPL_ID = ? AND OPL_ID_group IN (SELECT OPL_ID FROM tournaments WHERE (eventType='group' OR (eventType = 'league' AND format = 'swiss') OR eventType='wildcard') AND OPL_ID_top_parent = ?) ORDER BY OPL_ID_group", [$teamID, $tournamentID])->fetch_all(MYSQLI_ASSOC);
$team_solo = $dbcn->execute_query("SELECT * FROM teams WHERE OPL_ID = ?", [$teamID])->fetch_assoc();

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
if ($team_solo == NULL) {
	echo create_html_head_elements(title: "Team nicht gefunden | Uniliga LoL - Übersicht");
	echo "<body class='$lightmode'>";
	echo create_header(title: "error");
	echo "<div style='text-align: center'>Kein Team unter der angegebenen ID gefunden!</div></body>";
	exit();
}

// initial neueste Gruppe/Wildcard auswählen
$team = end($team_groups);

$group = $dbcn->execute_query("SELECT * FROM tournaments WHERE (eventType='group' OR (eventType = 'league' AND format = 'swiss') OR eventType = 'wildcard') AND OPL_ID = ?", [$team["OPL_ID_group"]])->fetch_assoc();
$league = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType='league' AND OPL_ID = ?", [$group["OPL_ID_parent"]])->fetch_assoc();
if ($group["format"] == "swiss" || $group["eventType"] == "wildcard") $league = $group;

$teams_from_groupDB = $dbcn->execute_query("SELECT * FROM teams JOIN teams_in_tournaments tit on teams.OPL_ID = tit.OPL_ID_team WHERE OPL_ID_group = ?", [$group["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
$teams_from_group = [];
foreach ($teams_from_groupDB as $i=>$team_from_group) {
	$teams_from_group[$team_from_group['OPL_ID']] = $team_from_group;
}

$team_name_now = $dbcn->execute_query("SELECT name FROM team_name_history WHERE OPL_ID_team = ? AND (update_time < ? OR ? IS NULL) ORDER BY update_time DESC", [$teamID,$tournament["dateEnd"],$tournament["dateEnd"]])->fetch_column();
$team["name"] = $team_name_now;

$t_name_clean = preg_replace("/LoL\s/i","",$tournament["name"]);
echo create_html_head_elements(title: "{$team_name_now} - Statistiken | $t_name_clean", loggedin: $logged_in);


?>
<body class="statistics <?php echo "$lightmode"?>">
<?php

$pageurl = $_SERVER['REQUEST_URI'];
$opl_tourn_url = "https://www.opleague.pro/event/";

echo create_header($dbcn,"tournament", $tournamentID);
echo create_tournament_nav_buttons($tournamentID, $dbcn,"",$league['OPL_ID'],$group['OPL_ID']);
echo create_team_nav_buttons($tournamentID,$group["OPL_ID"],$team,"stats");

$players = $dbcn->execute_query("SELECT * FROM players JOIN players_in_teams_in_tournament pit on players.OPL_ID = pit.OPL_ID_player AND pit.OPL_ID_tournament = ? LEFT JOIN stats_players_teams_tournaments spit ON pit.OPL_ID_player = spit.OPL_ID_player AND spit.OPL_ID_team = pit.OPL_ID_team AND spit.OPL_ID_tournament = ? WHERE pit.OPL_ID_team = ? ", [$tournamentID, $tournamentID, $teamID])->fetch_all(MYSQLI_ASSOC);
$teamstats = $dbcn->execute_query("SELECT * FROM stats_teams_in_tournaments WHERE OPL_ID_team = ? AND OPL_ID_tournament = ?", [$teamID, $tournamentID])->fetch_assoc();



$patches = $dbcn->execute_query("SELECT patch FROM local_patches WHERE data IS TRUE AND champion_webp IS TRUE AND item_webp IS TRUE AND runes_webp IS TRUE AND spell_webp IS TRUE")->fetch_all();
$patches = array_merge(...$patches);
usort($patches, "version_compare");
$latest_patch = end($patches);

$games_played = $teamstats['games_played'] ?? 0;


echo "<main>";
if ($games_played == 0) {
	echo "<span>Dieses Team hat noch keine Spiele gespielt</span>";
} else {
	echo "<span>Spiele: ".$games_played." | Siege: ".$teamstats['games_won']." (".round($teamstats['games_won']/$games_played*100,2)."%)</span>";
	echo "<span>durchschn. Zeit zum Sieg: ".date("i:s",$teamstats['avg_win_time'])."</span>";

	$players_by_name = array();
	$team_roles = array("top"=>array(),"jungle"=>array(),"middle"=>array(),"bottom"=>array(),"utility"=>array());
	foreach ($players as $player) {
		$roles = json_decode($player["roles"],true);
		if ($roles != NULL) {
			foreach ($roles as $role=>$role_num) {
				if ($role_num > 0) {
					$team_roles[$role][$player["name"]] = $role_num;
				}
			}
		}
		$players_by_name[$player["name"]] = $player;
	}
	$players_to_show = array();
	$players_not_to_show = array();
	echo "<div class='teamroles-wrapper'><div class='teamroles'>";
	foreach ($team_roles as $role=>$role_players) {
		arsort($role_players);
		echo "<div class='role'>
                    <div class='svg-wrapper role'>".file_get_contents(__DIR__."/../ddragon/img/positions/position-$role-light.svg")."</div>";
		echo "<div class='roleplayers'>";
		$count_role_players = 0;
		foreach ($role_players as $role_player=>$role_player_num) {
			$selected = " selected-player-table";
			if ($count_role_players > 0){
				echo "<div class='divider-vert'></div>";
				$selected = "";
				if (!in_array($role_player,$players_to_show) && !in_array($role_player,$players_not_to_show)) {
					$players_not_to_show[] = $role_player;
				}
			}
			if ($selected !== "") {
				if (!in_array($role_player,$players_to_show)) {
					$players_to_show[] = $role_player;
				}
				if (in_array($role_player,$players_not_to_show)) {
					if (($key = array_search($role_player,$players_not_to_show)) !== false) {
						array_splice($players_not_to_show,$key,1);
					}
				}
			}
            echo "<button type='button' class='role-playername$selected tooltip' data-name='{$players_by_name[$role_player]["riotID_name"]}#{$players_by_name[$role_player]["riotID_tag"]}'>".$players_by_name[$role_player]["riotID_name"]." ({$role_player_num}x) <span class='tooltiptext riot-id'>{$players_by_name[$role_player]["riotID_name"]}#{$players_by_name[$role_player]["riotID_tag"]}</span></button>";
			$count_role_players++;
		}
		echo "</div>";
		echo "</div>";
	}
	echo "</div></div>";

	$champs_played = json_decode($teamstats['champs_played'], true);
	arsort($champs_played);
	$champs_banned_against = json_decode($teamstats['champs_banned_against'], true);
	arsort($champs_banned_against);
	$champs_played_against = json_decode($teamstats['champs_played_against'],true);
	arsort($champs_played_against);
	$champs_banned = json_decode($teamstats['champs_banned'],true);
	arsort($champs_banned);
	$champs_presence = array();
	$champs_presence_only = array();
	foreach ($champs_played as $champ=>$champ_num) {
		$champs_presence[$champ] = array("played"=>$champ_num['games'],"banned_against"=>0,"played_against"=>0,"banned"=>0,"wins"=>$champ_num['wins'],"total"=>$champ_num['games']);
		$champs_presence_only[$champ] = $champ_num['games'];
	}
	foreach ($champs_banned_against as $champ=>$champ_num) {
		if (array_key_exists($champ,$champs_presence)) {
			$champs_presence[$champ]["banned_against"] += $champ_num;
			$champs_presence[$champ]["total"] += $champ_num;
			$champs_presence_only[$champ] += $champ_num;
		} else {
			$champs_presence[$champ] = array("played"=>0,"banned_against"=>$champ_num,"played_against"=>0,"banned"=>0,"wins"=>0,"total"=>$champ_num);
			$champs_presence_only[$champ] = $champ_num;
		}
	}
	foreach ($champs_played_against as $champ=>$champ_num) {
		if (array_key_exists($champ,$champs_presence)) {
			$champs_presence[$champ]["played_against"] += $champ_num;
			$champs_presence[$champ]["total"] += $champ_num;
			$champs_presence_only[$champ] += $champ_num;
		} else {
			$champs_presence[$champ] = array("played"=>0,"banned_against"=>0,"played_against"=>$champ_num,"banned"=>0,"wins"=>0,"total"=>$champ_num);
			$champs_presence_only[$champ] = $champ_num;
		}
	}
	foreach ($champs_banned as $champ=>$champ_num) {
		if (array_key_exists($champ,$champs_presence)) {
			$champs_presence[$champ]["banned"] += $champ_num;
			$champs_presence[$champ]["total"] += $champ_num;
			$champs_presence_only[$champ] += $champ_num;
		} else {
			$champs_presence[$champ] = array("played"=>0,"banned_against"=>0,"played_against"=>0,"banned"=>$champ_num,"wins"=>0,"total"=>$champ_num);
			$champs_presence_only[$champ] = $champ_num;
		}
	}
	arsort($champs_presence_only);

    $hidden_pt_columns = playertables_extended() ? "" : "hidden";
    $checked_pt_columns = playertables_extended() ? "checked" : "";

	echo "<div class='stattables'>";
	echo "<div class='playertable-header'>
		        <h3>Spieler</h3>
                <button title='Tabellen erweitern' class='pt-expand-all'><div class='material-symbol'>".file_get_contents(__DIR__."/../icons/material/unfold_more.svg")."</div></button>
                <button title='Tabellen reduzieren' class='pt-collapse-all'><div class='material-symbol'>".file_get_contents(__DIR__."/../icons/material/unfold_less.svg")."</div></button>
                <button class='button pt-moreinfo'><input type='checkbox' name='moreinfo' $checked_pt_columns class='controlled pt-moreinfo-checkbox'><span>erweiterte Statistiken</span></button>
              </div>";
	echo "<div class='table playerstable'>";
	for ($index=0; $index < count($players); $index++) {
		if ($index < count($players_to_show)) {
			$player_to_show = $players_to_show[$index];
			$player = $players_by_name[$player_to_show];
			$dontshow = "";
			$roleclass = " role".$index;
		} else {
			$new_index = $index - count($players_to_show);
			if ($new_index < count($players_not_to_show)) {
				$player_not_to_show = $players_not_to_show[$new_index];
				$player = $players_by_name[$player_not_to_show];
				$dontshow = " hidden-table";
				$roleclass = "";
			} else {
				break;
			}
		}
		$player_champs = json_decode($player['champions'],true);
		if (count($player_champs) === 0) {
			continue;
		}
		echo "<div class='playertable$dontshow$roleclass'>";
		arsort($player_champs);
		echo "<h4 class='tooltip' data-name='{$player["riotID_name"]}#{$player["riotID_tag"]}'>".$player['riotID_name']."<span class='tooltiptext riot-id'>{$player["riotID_name"]}#{$player["riotID_tag"]}</span></h4>";
		if (count($player_champs) > 5) {
			echo "<table class='collapsed'>";
		} else {
			echo "<table>";
		}
		echo "
                <tr>
                    <th></th>
                    <th class='sortable picks_col sortedby desc'>".populate_th("P","Picks",true)."</th>
                    <th class='sortable wins_col'>".populate_th("W","Wins")."</th>
                    <th class='sortable winrate_col'>".populate_th("W%","Winrate")."</th>
                    <th class='sortable kda_col customsort $hidden_pt_columns'>".populate_th("KDA","Kills/Deaths/Assists")."</th>
                </tr>";
		foreach ($player_champs as $champ_name => $champ) {
            $divisionsafe_deaths = ($champ["deaths"] == 0) ? 1 : $champ["deaths"];
            $kda_ratio = round(($champ["kills"] + $champ["assists"]) / $divisionsafe_deaths, 2);
            $kills_ratio = round($champ['kills'] / $champ['games'], 1);
            $deaths_ratio = round($champ['deaths'] / $champ['games'], 1);
            $assists_ratio = round($champ['assists'] / $champ['games'], 1);
			echo "
                <tr>
                    <td class='champion'><img src='/ddragon/$latest_patch/img/champion/$champ_name.webp' alt='$champ_name'></td>
                    <td class='picks_col'>".$champ['games']."</td>
                    <td class='wins_col'>".$champ['wins']."</td>
                    <td class='winrate_col'>".round(($champ['wins'] / $champ['games']) * 100, 2)."%</td>
                    <td class='kda_col $hidden_pt_columns' data-customsort = '$kda_ratio'>".$kills_ratio." / ".$deaths_ratio." / ".$assists_ratio."</td>
                </tr>";
		}
		if (count($player_champs) > 5) {
			echo "
                <tr class='expand-table'>
                    <td colspan='5'><div class='material-symbol'>".file_get_contents(__DIR__."/../icons/material/expand_less.svg")."</div></td>
                </tr>";
		}
		echo "</table>";
		echo "</div>";
	}
	echo "</div>"; // div.table.playerstable


	echo "<div class='table-wrapper'>";

	echo create_dropdown("stat-tables",["all"=>"Gesamt-Tabelle","single"=>"Einzel-Tabellen"]);

	echo "<div class='champstattables entire'>";
	echo "<div class='table pickstable'><h3>Championstatistiken</h3><table>";
	echo "
            <tr>
                <th></th>
                <th class='sortable sortedby desc'>".populate_th("P","eigene Picks",true)."</th>
                <th class='sortable'>".populate_th("P(g)","gegnerische Picks")."</th>
                <th class='sortable'>".populate_th("B","eigene Bans")."</th>
                <th class='sortable'>".populate_th("B(g)","gegnerische Bans")."</th>
                <th class='sortable'>".populate_th("W%","eigene Winrate")."</th>
                <th class='sortable'>".populate_th("PB%","Gesamte Pick/Banrate")."</th>
            </tr>";
	foreach ($champs_presence as $champ_name => $champ) {
		if ($champ['played'] === 0) {
			$winrate = "-";
		} else {
			$winrate = round(($champ['wins']/$champ['played'])*100,2)."% (".$champ['wins'].")";
		}
		echo "
            <tr>
                <td class='champion'><img src='/ddragon/$latest_patch/img/champion/$champ_name.webp' alt='$champ_name'></td>
                <td>".$champ['played']."</td>
                <td>".$champ['played_against']."</td>
                <td>".$champ['banned']."</td>
                <td>".$champ['banned_against']."</td>
                <td>".$winrate."</td>
                <td>".round(($champ['total']/$games_played)*100,2)."% (". $champ['total'].")</td>
            </tr>";
	}
	echo "</table></div>";
	echo "</div>"; //champstattables entire


	echo "<div class='champstattables singles' style='display: none'>";

	echo "<div class='table pickstable'><h3>Eigene Picks</h3><table>";
	echo "
            <tr>
                <th></th>
                <th class='sortable sortedby desc'>".populate_th("P","Picks",true)."</th>
                <th class='sortable'>".populate_th("W%","Winrate")."</th>
            </tr>";
	foreach ($champs_played as $champ_name => $champ) {
		echo "
            <tr>
                <td class='champion'><img src='/ddragon/$latest_patch/img/champion/$champ_name.webp' alt='$champ_name'></td>
                <td>".$champ['games']."</td>
                <td>".round(($champ['wins']/$champ['games'])*100,2)."% (".$champ['wins'].")</td>
            </tr>";
	}
	echo "</table></div>";
	echo "<div class='divider-vert'></div>";

	echo "<div class='table pickstable'><h3>Gegner Picks</h3><table>";
	echo "
            <tr>
                <th></th>
                <th class='sortable sortedby desc'>".populate_th("P","Picks",true)."</th>
            </tr>";
	foreach ($champs_played_against as $champ_name => $champ_num) {
		echo "
            <tr>
                <td class='champion'><img src='/ddragon/$latest_patch/img/champion/$champ_name.webp' alt='$champ_name'></td>
                <td>".$champ_num."</td>
            </tr>";
	}
	echo "</table></div>";
	echo "<div class='divider-vert'></div>";

	echo "<div class='table banstable'><h3>Gegner Bans</h3><table>";
	echo "
            <tr>
                <th></th>
                <th class='sortable sortedby desc'>".populate_th("B","Bans",true)."</th>
            </tr>";
	foreach ($champs_banned_against as $champ_name => $champ_num) {
		echo "
            <tr>
                <td class='champion'><img src='/ddragon/$latest_patch/img/champion/$champ_name.webp' alt='$champ_name'></td>
                <td>".$champ_num."</td>
            </tr>";
	}
	echo "</table></div>";
	echo "<div class='divider-vert'></div>";

	echo "<div class='table banstable'><h3>eigene Bans</h3><table>";
	echo "
            <tr>
                <th></th>
                <th class='sortable sortedby desc'>".populate_th("B","Bans",true)."</th>
            </tr>";
	foreach ($champs_banned as $champ_name => $champ_num) {
		echo "
            <tr>
                <td class='champion'><img src='/ddragon/$latest_patch/img/champion/$champ_name.webp' alt='$champ_name'></td>
                <td>".$champ_num."</td>
            </tr>";
	}
	echo "</table></div>";

	echo "<div class='divider-vert'></div>";

	echo "<div class='table presencetable'><h3>Champ-Präsenz</h3><table>";
	echo "
            <tr>
                <th></th>
                <th class='sortable sortedby desc'>".populate_th("PB%","Gesamte Pick/Banrate",true)."</th>
            </tr>";
	foreach ($champs_presence_only as $champ_name => $champ_num) {
		echo "
            <tr>
                <td class='champion'><img src='/ddragon/$latest_patch/img/champion/$champ_name.webp' alt='$champ_name'></td>
                <td>".round(($champ_num/$games_played)*100,2)."% (".$champ_num.")</td>
            </tr>";
	}
	echo "</table></div>";

	echo "</div>"; // div.champstattables singles
	echo "</div>"; // div.table-wrapper
	echo "</div>"; // div.stattables
}
echo "</main>";


?>
</body>
</html>