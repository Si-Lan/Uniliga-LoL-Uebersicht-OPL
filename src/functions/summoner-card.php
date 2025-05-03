<?php

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