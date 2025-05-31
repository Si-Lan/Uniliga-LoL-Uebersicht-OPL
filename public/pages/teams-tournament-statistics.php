<?php

use App\Core\Utilities\UserContext;
use App\Domain\Repositories\PatchRepository;
use App\Domain\Repositories\PlayerInTeamInTournamentRepository;
use App\Domain\Repositories\TeamInTournamentRepository;
use App\Domain\Repositories\TeamInTournamentStageRepository;
use App\Domain\Services\EntitySorter;
use App\UI\Components\Navigation\Header;
use App\UI\Components\Navigation\TeamHeaderNav;
use App\UI\Components\Navigation\TournamentNav;
use App\UI\Enums\HeaderType;
use App\UI\Page\PageMeta;

$teamInTournamentRepo = new TeamInTournamentRepository();
$teamInTournamentStageRepo = new TeamInTournamentStageRepository();
$playerInTeamInTournamentRepo = new PlayerInTeamInTournamentRepository();
$patchRepo = new PatchRepository();

$teamInTournament = $teamInTournamentRepo->findByTeamIdAndTournamentId($_GET["team"], $_GET["tournament"]);

// alle Gruppen / Wildcard-Turniere / Playoffs, in denen das Team spielt, holen und sortieren
$teamInTournamentStages = $teamInTournamentStageRepo->findAllbyTeamInTournament($teamInTournament);
$teamInTournamentStages = EntitySorter::sortTeamInTournamentStages($teamInTournamentStages);

// Über Routing ist bereits klar, dass Team und Turnier existieren, hier wird noch geprüft, ob das Team auch im Turnier spielt
if (count($teamInTournamentStages) === 0) {
	trigger404("team-in-tournament");
	exit();
}
$teamInTournamentStage = end($teamInTournamentStages);

$pageMeta = new PageMeta(
	title: "$teamInTournament->nameInTournament - Statistiken | ".$teamInTournament->tournament->getShortName(),
	bodyClass: 'statistics'
);

echo new Header(HeaderType::TOURNAMENT, $teamInTournament->tournament);
echo new TournamentNav($teamInTournament->tournament);
echo new TeamHeaderNav($teamInTournamentStage->teamInRootTournament, "stats");

$players = $playerInTeamInTournamentRepo->findAllByTeamInTournament($teamInTournament);

$latest_patch = $patchRepo->findLatestPatchWithAllData();


echo "<main>";
if ($teamInTournament->gamesPlayed == 0) {
	echo "<span>Dieses Team hat noch keine Spiele gespielt</span>";
} else {
	echo "<span>Spiele: ".$teamInTournament->gamesPlayed." | Siege: ".$teamInTournament->gamesWon." (".round($teamInTournament->gamesWon/$teamInTournament->gamesPlayed*100,2)."%)</span>";
	echo "<span>durchschn. Zeit zum Sieg: ".date("i:s",$teamInTournament->avgWinTime)."</span>";

	$players_by_name = array();
	$team_roles = array("top"=>array(),"jungle"=>array(),"middle"=>array(),"bottom"=>array(),"utility"=>array());
	foreach ($players as $player) {
		foreach ($player->stats->roles as $role=>$role_num) {
			if ($role_num > 0) {
				$team_roles[$role][$player->player->id] = $role_num;
			}
		}
		$players_by_name[$player->player->id] = $player;
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
            echo "<button type='button' class='role-playername$selected tooltip' data-name='{$players_by_name[$role_player]->player->riotIdName}#{$players_by_name[$role_player]->player->riotIdTag}'>".$players_by_name[$role_player]->player->riotIdName." ({$role_player_num}x) <span class='tooltiptext riot-id'>{$players_by_name[$role_player]->player->riotIdName}#{$players_by_name[$role_player]->player->riotIdTag}</span></button>";
			$count_role_players++;
		}
		echo "</div>";
		echo "</div>";
	}
	echo "</div></div>";

	arsort($teamInTournament->champsPlayed);
	arsort($teamInTournament->champsBannedAgainst);
	arsort($teamInTournament->champsPlayedAgainst);
	arsort($teamInTournament->champsBanned);
	$champs_presence = array();
	$champs_presence_only = array();
	foreach ($teamInTournament->champsPlayed as $champ=>$champ_num) {
		$champs_presence[$champ] = array("played"=>$champ_num['games'],"banned_against"=>0,"played_against"=>0,"banned"=>0,"wins"=>$champ_num['wins'],"total"=>$champ_num['games']);
		$champs_presence_only[$champ] = $champ_num['games'];
	}
	foreach ($teamInTournament->champsBannedAgainst as $champ=>$champ_num) {
		if (array_key_exists($champ,$champs_presence)) {
			$champs_presence[$champ]["banned_against"] += $champ_num;
			$champs_presence[$champ]["total"] += $champ_num;
			$champs_presence_only[$champ] += $champ_num;
		} else {
			$champs_presence[$champ] = array("played"=>0,"banned_against"=>$champ_num,"played_against"=>0,"banned"=>0,"wins"=>0,"total"=>$champ_num);
			$champs_presence_only[$champ] = $champ_num;
		}
	}
	foreach ($teamInTournament->champsPlayedAgainst as $champ=>$champ_num) {
		if (array_key_exists($champ,$champs_presence)) {
			$champs_presence[$champ]["played_against"] += $champ_num;
			$champs_presence[$champ]["total"] += $champ_num;
			$champs_presence_only[$champ] += $champ_num;
		} else {
			$champs_presence[$champ] = array("played"=>0,"banned_against"=>0,"played_against"=>$champ_num,"banned"=>0,"wins"=>0,"total"=>$champ_num);
			$champs_presence_only[$champ] = $champ_num;
		}
	}
	foreach ($teamInTournament->champsBanned as $champ=>$champ_num) {
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

    $hidden_pt_columns = UserContext::playerTablesExtended() ? "" : "hidden";
    $checked_pt_columns = UserContext::playerTablesExtended() ? "checked" : "";

	echo "<div class='stattables'>";
	echo "<div class='playertable-header'>
		        <h3>Spieler</h3>
                <button title='Tabellen erweitern' class='pt-expand-all'><div class='material-symbol'>".file_get_contents(__DIR__."/../assets/icons/material/unfold_more.svg")."</div></button>
                <button title='Tabellen reduzieren' class='pt-collapse-all'><div class='material-symbol'>".file_get_contents(__DIR__."/../assets/icons/material/unfold_less.svg")."</div></button>
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
		if (count($player->stats->champions) === 0) {
			continue;
		}
		echo "<div class='playertable$dontshow$roleclass'>";
		arsort($player->stats->champions);
		echo "<h4 class='tooltip' data-name='{$player->player->riotIdName}#{$player->player->riotIdTag}'>".$player->player->riotIdName."<span class='tooltiptext riot-id'>{$player->player->riotIdName}#{$player->player->riotIdTag}</span></h4>";
		if (count($player->stats->champions) > 5) {
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
		foreach ($player->stats->champions as $champ_name => $champ) {
            $divisionsafe_deaths = ($champ["deaths"] == 0) ? 1 : $champ["deaths"];
            $kda_ratio = round(($champ["kills"] + $champ["assists"]) / $divisionsafe_deaths, 2);
            $kills_ratio = round($champ['kills'] / $champ['games'], 1);
            $deaths_ratio = round($champ['deaths'] / $champ['games'], 1);
            $assists_ratio = round($champ['assists'] / $champ['games'], 1);
			echo "
                <tr>
                    <td class='champion'><img src='/ddragon/$latest_patch->patchNumber/img/champion/$champ_name.webp' alt='$champ_name'></td>
                    <td class='picks_col'>".$champ['games']."</td>
                    <td class='wins_col'>".$champ['wins']."</td>
                    <td class='winrate_col'>".round(($champ['wins'] / $champ['games']) * 100, 2)."%</td>
                    <td class='kda_col $hidden_pt_columns' data-customsort = '$kda_ratio'>".$kills_ratio." / ".$deaths_ratio." / ".$assists_ratio."</td>
                </tr>";
		}
		if (count($player->stats->champions) > 5) {
			echo "
                <tr class='expand-table'>
                    <td colspan='5'><div class='material-symbol'>".file_get_contents(__DIR__."/../assets/icons/material/expand_less.svg")."</div></td>
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
                <td class='champion'><img src='/ddragon/$latest_patch->patchNumber/img/champion/$champ_name.webp' alt='$champ_name'></td>
                <td>".$champ['played']."</td>
                <td>".$champ['played_against']."</td>
                <td>".$champ['banned']."</td>
                <td>".$champ['banned_against']."</td>
                <td>".$winrate."</td>
                <td>".round(($champ['total']/$teamInTournament->gamesPlayed)*100,2)."% (". $champ['total'].")</td>
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
	foreach ($teamInTournament->champsPlayed as $champ_name => $champ) {
		echo "
            <tr>
                <td class='champion'><img src='/ddragon/$latest_patch->patchNumber/img/champion/$champ_name.webp' alt='$champ_name'></td>
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
	foreach ($teamInTournament->champsPlayedAgainst as $champ_name => $champ_num) {
		echo "
            <tr>
                <td class='champion'><img src='/ddragon/$latest_patch->patchNumber/img/champion/$champ_name.webp' alt='$champ_name'></td>
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
	foreach ($teamInTournament->champsBannedAgainst as $champ_name => $champ_num) {
		echo "
            <tr>
                <td class='champion'><img src='/ddragon/$latest_patch->patchNumber/img/champion/$champ_name.webp' alt='$champ_name'></td>
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
	foreach ($teamInTournament->champsBanned as $champ_name => $champ_num) {
		echo "
            <tr>
                <td class='champion'><img src='/ddragon/$latest_patch->patchNumber/img/champion/$champ_name.webp' alt='$champ_name'></td>
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
                <td class='champion'><img src='/ddragon/$latest_patch->patchNumber/img/champion/$champ_name.webp' alt='$champ_name'></td>
                <td>".round(($champ_num/$teamInTournament->gamesPlayed)*100,2)."% (".$champ_num.")</td>
            </tr>";
	}
	echo "</table></div>";

	echo "</div>"; // div.champstattables singles
	echo "</div>"; // div.table-wrapper
	echo "</div>"; // div.stattables
}
echo "</main>";

function populate_th($maintext,$tooltiptext,$init=false) {
	if ($init) {
		$svg_code = file_get_contents(dirname(__DIR__,2)."/public/assets/icons/material/expand_more.svg");
	} else {
		$svg_code = file_get_contents(dirname(__DIR__,2)."/public/assets/icons/material/check_indeterminate_small.svg");
	}
	return "<span class='tooltip'>$maintext<span class='tooltiptext'>$tooltiptext</span><div class='material-symbol sort-direction'>".$svg_code."</div></span>";
}
?>