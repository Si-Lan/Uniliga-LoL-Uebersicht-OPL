<?php
function create_summonercard(mysqli $dbcn, $playerID, $tournamentID, $teamID = NULL, bool $collapsed=FALSE, bool $echo=FALSE):string {
	$player = $dbcn->execute_query("SELECT * FROM players p JOIN players_in_teams_in_tournament pitit on p.OPL_ID = pitit.OPL_ID_player AND OPL_ID_tournament = ? AND OPL_ID_team = ? LEFT JOIN stats_players_teams_tournaments spit ON p.OPL_ID = spit.OPL_ID_player AND pitit.OPL_ID_team = spit.OPL_ID_team AND pitit.OPL_ID_tournament = spit.OPL_ID_tournament WHERE p.OPL_ID = ?", [$tournamentID, $teamID, $playerID])->fetch_assoc();
	$player_rank = $dbcn->execute_query("SELECT * FROM players_season_rank WHERE OPL_ID_player = ? AND season = (SELECT tournaments.season FROM tournaments WHERE tournaments.OPL_ID = ?)", [$playerID, $tournamentID])->fetch_assoc();
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
	$return .= "<div class='summoner-card-wrapper'>";
	$return .= "
	<div class='summoner-card {$player['OPL_ID']} $sc_collapsed_state $player_removed_class' onclick='player_to_opgg_link(\"{$player['OPL_ID']}\",\"{$riotid_full}\")'>";
	$return .= "<input type='checkbox' name='OPGG' checked class='opgg-checkbox'>";
	$return .= "
	<span class='card-player'>
		{$player['name']}
	</span>
	<div class='divider'></div>
	<div class='card-summoner'>";
	if ($player["riotID_name"] != null) $return .= "
		<span class='card-riotid'>
			<span class='league-icon'>".file_get_contents(__DIR__."/../icons/LoL_Icon_Flat.svg")."</span>
			<span>{$player['riotID_name']}</span><span class='riot-id-tag'>$riot_tag</span>
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
			<img class='rank-emblem-mini' src='ddragon/img/ranks/mini-crests/{$player_tier}.svg' alt='$player_tier_cap'>
			$player_tier_cap $player_div $player_LP
		</div>";
	}

	$return .= "
			<div class='played-positions'>";
	$roles = json_decode($player['roles']);
	if ($roles != NULL) {
		foreach ($roles as $role=>$role_amount) {
			if ($role_amount != 0) {
				$return .= "
				<div class='role-single'>
					<div class='svg-wrapper role'>".file_get_contents(__DIR__."/../ddragon/img/positions/position-$role-light.svg")."</div>
					<span class='played-amount'>$role_amount</span>
				</div>";
			}
		}
	}
	$return .= "
		</div>"; // played-positions

	$return .= "
		<div class='played-champions'>";
	$champions = json_decode($player['champions'],true);
	if ($champions != NULL) {
		arsort($champions);
		$champs_cut = FALSE;
		if (count($champions) > 5) {
			$champions = array_slice($champions, 0, 5);
			$champs_cut = TRUE;
		}

		$patches = [];
		$dir = new DirectoryIterator(__DIR__."/../ddragon");
		foreach ($dir as $fileinfo) {
			if (!$fileinfo->isDot() && $fileinfo->getFilename() != "img" && $fileinfo->isDir()) {
				$patches[] = $fileinfo->getFilename();
			}
		}

		usort($patches, "version_compare");
		$patch = end($patches);

		foreach ($champions as $champion=>$champion_amount) {
			$return .= "
			<div class='champ-single'>
				<img src='./ddragon/{$patch}/img/champion/{$champion}.webp' alt='$champion'>
				<span class='played-amount'>".$champion_amount['games']."</span>
			</div>";
		}
		if ($champs_cut) {
			$return .= "
		<div class='champ-single'>
			<div class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/more_horiz.svg") ."</div>
		</div>";
		}
	}
	$return .= "
		</div>"; // played-champions
	$return .= "
	</div>"; // card-summoner
	$return .= "
	</div>"; // summoner-card
    $return .= "<a href='javascript:void(0)' class='open-playerhistory' onclick='popup_player(\"{$player["OPL_ID"]}\")'><div class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/info.svg") ."</div>Spieler-Details</a>";
    //$return .= "<a href='spieler/$playerID' class='open-playerhistory' ><div class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/info.svg") ."</div>Spieler-Details</a>";
	$return .= "<a href='https://www.op.gg/summoners/euw/$enc_riotid' target='_blank' class='op-gg-single'><div class='svg-wrapper op-gg'>".file_get_contents(__DIR__."/../img/opgglogo.svg")."</div></a>";
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
	$return .= "
	<div class='summoner-card {$player['OPL_ID']} collapsed$player_removed_class' onclick='player_to_opgg_link(\"{$player['OPL_ID']}\",\"{$riotid_full}\")'>";
	$return .= "<input type='checkbox' name='OPGG' checked class='opgg-checkbox'>";
	$return .= "
	<span class='card-player'>
		{$player['name']}
	</span>
	<div class='divider'></div>
	<div class='card-summoner'>";
	if ($player["riotID_name"] != null) $return .= "
		<span class='card-riotid'>
			<span class='league-icon'>".file_get_contents(__DIR__."/../icons/LoL_Icon_Flat.svg")."</span>
			<span>{$player['riotID_name']}</span><span class='riot-id-tag'>$riot_tag</span>
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
			<img class='rank-emblem-mini' src='ddragon/img/ranks/mini-crests/{$player_tier}.svg' alt='$player_tier_cap'>
			$player_tier_cap $player_div $player_LP
		</div>";
	}

	$return .= "
	</div>"; // card-summoner
	$return .= "
	</div>"; // summoner-card
	$return .= "<a href='javascript:void(0)' class='open-playerhistory' onclick='popup_player(\"{$player["OPL_ID"]}\")'><div class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/info.svg") ."</div>Spieler-Details</a>";
	//$return .= "<a href='spieler/$playerID' class='open-playerhistory' ><div class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/info.svg") ."</div>Spieler-Details</a>";
	$return .= "<a href='https://www.op.gg/summoners/euw/$enc_riotid' target='_blank' class='op-gg-single'><div class='svg-wrapper op-gg'>".file_get_contents(__DIR__."/../img/opgglogo.svg")."</div></a>";
	$return .= "</div>"; // summoner-card-wrapper

	return $return;
}