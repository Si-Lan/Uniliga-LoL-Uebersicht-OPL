<?php
function create_summonercard(mysqli $dbcn, $playerID, $tournamentID, $teamID = NULL, bool $collapsed=FALSE, bool $echo=FALSE):string {
	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?",[$tournamentID])->fetch_assoc();
	$season_1 = $tournament["ranked_season"];
	$split_1 = $tournament["ranked_split"];
	$player = $dbcn->execute_query("SELECT * FROM players p JOIN players_in_teams_in_tournament pitit on p.OPL_ID = pitit.OPL_ID_player AND OPL_ID_tournament = ? AND OPL_ID_team = ? LEFT JOIN stats_players_teams_tournaments spit ON p.OPL_ID = spit.OPL_ID_player AND pitit.OPL_ID_team = spit.OPL_ID_team AND pitit.OPL_ID_tournament = spit.OPL_ID_tournament WHERE p.OPL_ID = ?", [$tournamentID, $teamID, $playerID])->fetch_assoc();
	$player_rank = $dbcn->execute_query("SELECT * FROM players_season_rank WHERE OPL_ID_player = ? AND season = ? AND split = ?", [$playerID, $season_1, $split_1])->fetch_assoc();
	$next_split = get_second_ranked_split_for_tournament($dbcn,$tournamentID);
	$season_2 = $next_split["season"] ?? null;
	$split_2 = $next_split["split"] ?? null;
	$player_rank_2 = $dbcn->execute_query("SELECT * FROM players_season_rank WHERE OPL_ID_player = ? AND season = ? AND split = ?", [$playerID, $season_2, $split_2])->fetch_assoc();
	$current_split = get_current_ranked_split($dbcn, $tournamentID);

	$return = "";
	$player_removed_class =  ($player["removed"] == 1) ? "player-removed" : "";
    if ($collapsed) {
		$sc_collapsed_state = "collapsed";
    } else {
		$sc_collapsed_state = "";
    }

	$enc_riotid = urlencode($player['riotID_name']??"")."-".urlencode($player['riotID_tag']??"");
	$riotid_full = $player['riotID_name']."#".$player['riotID_tag'];
	$riot_tag = ($player['riotID_tag'] != NULL && $player['riotID_tag'] != "") ? "#".$player['riotID_tag'] : "";

	$player_tier = $player_rank['rank_tier'] ?? null;
	$player_div = $player_rank['rank_div'] ?? null;
	$player_LP = NULL;
	if ($player_tier == "CHALLENGER" || $player_tier == "GRANDMASTER" || $player_tier == "MASTER") {
		$player_div = "";
		$player_LP = $player_rank["rank_LP"] ?? null;
	}
	$player_tier_2 = $player_rank_2['rank_tier'] ?? null;
	$player_div_2 = $player_rank_2['rank_div'] ?? null;
	$player_LP_2 = NULL;
	if ($player_tier_2 == "CHALLENGER" || $player_tier_2 == "GRANDMASTER" || $player_tier_2 == "MASTER") {
		$player_div_2 = "";
		$player_LP_2 = $player_rank_2["rank_LP"] ?? null;
	}

	$return .= "<div class='summoner-card-wrapper'>";
	if ($player["riotID_name"] == null) {
		$return .= "<div class='summoner-card {$player['OPL_ID']} $sc_collapsed_state $player_removed_class'>";
		$return .= "<input type='checkbox' name='OPGG' disabled class='opgg-checkbox'>";
	} elseif ($player["removed"]) {
		$return .= "<div class='summoner-card {$player['OPL_ID']} $sc_collapsed_state $player_removed_class' onclick='player_to_opgg_link(\"{$player['OPL_ID']}\",\"{$riotid_full}\")'>";
		$return .= "<input type='checkbox' name='OPGG' class='opgg-checkbox'>";
	} else {
		$return .= "<div class='summoner-card {$player['OPL_ID']} $sc_collapsed_state $player_removed_class' onclick='player_to_opgg_link(\"{$player['OPL_ID']}\",\"{$riotid_full}\")'>";
		$return .= "<input type='checkbox' name='OPGG' checked class='opgg-checkbox'>";
	}
	$return .= "
	<span class='card-player'>
		{$player['name']}
	</span>
	<div class='divider'></div>
	<div class='card-summoner'>";
	if ($player["riotID_name"] != null) $return .= "
		<span class='card-riotid'>
			<span class='league-icon'>".file_get_contents(dirname(__DIR__,2)."/public/icons/LoL_Icon_Flat.svg")."</span>
			<span class='riot-id'>{$player['riotID_name']}<span class='riot-id-tag'>$riot_tag</span></span>
		</span>";

	if ($current_split == "$season_2-$split_2") {
		$rank_hide_1 = "display: none";
		$rank_hide_2 = "";
	} else {
		$rank_hide_1 = "";
		$rank_hide_2 = "display: none";
	}

	if ($player_tier != NULL) {
		$player_tier = strtolower($player_tier);
        $player_tier_cap = ucfirst($player_tier);
		if ($player_LP != NULL) {
			$player_LP = "(".$player_LP." LP)";
		} else {
			$player_LP = "";
		}
		$return .= "
		<div class='card-rank split_rank_element ranked-split-$season_1-$split_1' style='$rank_hide_1'>
			<img class='rank-emblem-mini' src='/ddragon/img/ranks/mini-crests/{$player_tier}.svg' alt='$player_tier_cap'>
			$player_tier_cap $player_div $player_LP
		</div>";
	}
	if ($player_tier_2 != NULL) {
		$player_tier_2 = strtolower($player_tier_2);
		$player_tier_cap_2 = ucfirst($player_tier_2);
		if ($player_LP_2 != NULL) {
			$player_LP_2 = "(".$player_LP_2." LP)";
		} else {
			$player_LP_2 = "";
		}
		$return .= "
		<div class='card-rank split_rank_element ranked-split-$season_2-$split_2' style='$rank_hide_2'>
			<img class='rank-emblem-mini' src='/ddragon/img/ranks/mini-crests/{$player_tier_2}.svg' alt='$player_tier_cap_2'>
			$player_tier_cap_2 $player_div_2 $player_LP_2
		</div>";
	}

	$return .= "
			<div class='played-positions'>";
	$roles = $player['roles'] != null ? json_decode($player['roles']) : null;
	if ($roles != NULL) {
		foreach ($roles as $role=>$role_amount) {
			if ($role_amount != 0) {
				$return .= "
				<div class='role-single'>
					<div class='svg-wrapper role'>".file_get_contents(dirname(__DIR__,2)."/public/ddragon/img/positions/position-$role-light.svg")."</div>
					<span class='played-amount'>$role_amount</span>
				</div>";
			}
		}
	}
	$return .= "
		</div>"; // played-positions

	$return .= "
		<div class='played-champions'>";
	$champions = $player['champions'] != null ? json_decode($player['champions'],true) : null;
	if ($champions != NULL) {
		arsort($champions);
		$champs_cut = FALSE;
		if (count($champions) > 5) {
			$champions = array_slice($champions, 0, 5);
			$champs_cut = TRUE;
		}

		$patches = $dbcn->execute_query("SELECT patch FROM local_patches WHERE data IS TRUE AND champion_webp IS TRUE AND item_webp IS TRUE AND runes_webp IS TRUE AND spell_webp IS TRUE")->fetch_all();
		$patches = array_merge(...$patches);
		usort($patches, "version_compare");
		$patch = end($patches);

		foreach ($champions as $champion=>$champion_amount) {
			$return .= "
			<div class='champ-single'>
				<img src='/ddragon/{$patch}/img/champion/{$champion}.webp' alt='$champion'>
				<span class='played-amount'>".$champion_amount['games']."</span>
			</div>";
		}
		if ($champs_cut) {
			$return .= "
		<div class='champ-single'>
			<div class='material-symbol'>". file_get_contents(dirname(__DIR__,2)."/public/icons/material/more_horiz.svg") ."</div>
		</div>";
		}
	}
	$return .= "
		</div>"; // played-champions
	$return .= "
	</div>"; // card-summoner
	$return .= "
	</div>"; // summoner-card
    $return .= "<a href='javascript:void(0)' class='open-playerhistory' onclick='popup_player(\"{$player["OPL_ID"]}\")'>Spieler-Details</a>";
    //$return .= "<a href='spieler/$playerID' class='open-playerhistory' ><div class='material-symbol'>". file_get_contents(dirname(__DIR__,2)."/public/icons/material/info.svg") ."</div>Spieler-Details</a>";
	$return .= "<a href='https://www.op.gg/summoners/euw/$enc_riotid' target='_blank' class='op-gg-single'><div class='svg-wrapper op-gg'>".file_get_contents(dirname(__DIR__,2)."/public/img/opgglogo.svg")."</div></a>";
	$return .= "</div>"; // summoner-card-wrapper

	if ($echo) {
		echo $return;
	}
	return $return;
}

function create_summonercard_general(mysqli $dbcn, $playerID, $teamID = null):string {
	$player = $dbcn->execute_query("SELECT * FROM players p LEFT JOIN players_in_teams pit ON p.OPL_ID = pit.OPL_ID_player AND pit.OPL_ID_team = ? WHERE OPL_ID = ?", [$teamID,$playerID])->fetch_assoc();
	$player_rank = $dbcn->execute_query("SELECT * FROM players_season_rank WHERE OPL_ID_player = ? ORDER BY season DESC", [$playerID])->fetch_assoc();
	$player_removed_class =  ($player["removed"]??false) ? " player-removed" : "";
	$return = "";
	$enc_riotid = urlencode($player['riotID_name']??"")."-".urlencode($player['riotID_tag']??"");
	$riotid_full = $player['riotID_name']."#".$player['riotID_tag'];
	$riot_tag = ($player['riotID_tag'] != NULL && $player['riotID_tag'] != "") ? "#".$player['riotID_tag'] : "";
	$player_tier = $player_rank['rank_tier'] ?? null;
	$player_div = $player_rank['rank_div'] ?? null;
	$player_LP = NULL;
	if ($player_tier == "CHALLENGER" || $player_tier == "GRANDMASTER" || $player_tier == "MASTER") {
		$player_div = "";
		$player_LP = $player_rank["rank_LP"] ?? null;
	}
	$return .= "<div class='summoner-card-wrapper'>";
	if ($player["riotID_name"] != null) {
		$return .= "<div class='summoner-card {$player['OPL_ID']} collapsed$player_removed_class' onclick='player_to_opgg_link(\"{$player['OPL_ID']}\",\"{$riotid_full}\")'>";
		$return .= "<input type='checkbox' name='OPGG' checked class='opgg-checkbox'>";
	} else {
		$return .= "<div class='summoner-card {$player['OPL_ID']} collapsed$player_removed_class'>";
		$return .= "<input type='checkbox' name='OPGG' disabled class='opgg-checkbox'>";
	}
	$return .= "
	<span class='card-player'>
		{$player['name']}
	</span>
	<div class='divider'></div>
	<div class='card-summoner'>";
	if ($player["riotID_name"] != null) $return .= "
		<span class='card-riotid'>
			<span class='league-icon'>".file_get_contents(dirname(__DIR__,2)."/public/icons/LoL_Icon_Flat.svg")."</span>
			<span class='riot-id'>{$player['riotID_name']}<span class='riot-id-tag'>$riot_tag</span></span>
		</span>";

	if ($player_tier != NULL) {
		$player_tier = strtolower($player_tier);
		$player_tier_cap = ucfirst($player_tier);
		if ($player_LP != NULL) {
			$player_LP = "(".$player_LP." LP)";
		} else {
			$player_LP = "";
		}
		$return .= "
		<div class='card-rank'>
			<img class='rank-emblem-mini' src='/ddragon/img/ranks/mini-crests/{$player_tier}.svg' alt='$player_tier_cap'>
			$player_tier_cap $player_div $player_LP
		</div>";
	}

	$return .= "
	</div>"; // card-summoner
	$return .= "
	</div>"; // summoner-card
	$return .= "<a href='javascript:void(0)' class='open-playerhistory' onclick='popup_player(\"{$player["OPL_ID"]}\")'>Spieler-Details</a>";
	//$return .= "<a href='spieler/$playerID' class='open-playerhistory' ><div class='material-symbol'>". file_get_contents(dirname(__DIR__,2)."/public/icons/material/info.svg") ."</div>Spieler-Details</a>";
	$return .= "<a href='https://www.op.gg/summoners/euw/$enc_riotid' target='_blank' class='op-gg-single'><div class='svg-wrapper op-gg'>".file_get_contents(dirname(__DIR__,2)."/public/img/opgglogo.svg")."</div></a>";
	$return .= "</div>"; // summoner-card-wrapper

	return $return;
}