<?php
function create_summonercard(mysqli $dbcn, $playerID, $tournamentID, bool $collapsed=FALSE, bool $echo=FALSE):string {
	$player = $dbcn->execute_query("SELECT * FROM players p LEFT JOIN stats_players_in_tournaments spit ON p.OPL_ID = spit.OPL_ID_player AND spit.OPL_ID_tournament = ? WHERE p.OPL_ID = ?", [$tournamentID, $playerID])->fetch_assoc();
	$return = "";
    if ($collapsed) {
		$sc_collapsed_state = "collapsed";
    } else {
		$sc_collapsed_state = "";
    }
	$enc_summoner = urlencode($player['summonerName']);
	$player_tier = $player['rank_tier'];
	$player_div = $player['rank_div'];
	$player_LP = NULL;
	if ($player_tier == "CHALLENGER" || $player_tier == "GRANDMASTER" || $player_tier == "MASTER") {
		$player_div = "";
		$player_LP = $player["rank_LP"];
	}
	$return .= "<div class='summoner-card-wrapper'>";
	$return .= "
	<div class='summoner-card {$player['OPL_ID']} $sc_collapsed_state' onclick='player_to_opgg_link(\"{$player['OPL_ID']}\",\"{$player['summonerName']}\")'>";
	$return .= "<input type='checkbox' name='OPGG' checked class='opgg-checkbox'>";
	$return .= "
	<span class='card-player'>
		{$player['name']}
	</span>
	<div class='divider'></div>
	<div class='card-summoner'>
		<span>
			{$player['summonerName']}
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
		$dir = new DirectoryIterator(dirname(__FILE__) . "/ddragon");
		foreach ($dir as $fileinfo) {
			if (!$fileinfo->isDot() && $fileinfo->getFilename() != "img") {
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
	if ($player["PUUID"] != NULL) {
		$return .= "<a href='javascript:void(0)' class='open-playerhistory' onclick='popup_player(\"{$player["PUUID"]}\")'><div class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/history.svg") ."</div>Spieler-History</a>";
	}
	$return .= "<a href='https://www.op.gg/summoners/euw/$enc_summoner' target='_blank' class='op-gg-single'><div class='svg-wrapper op-gg'>".file_get_contents(__DIR__."/../img/opgglogo.svg")."</div></a>";
	$return .= "</div>"; // summoner-card-wrapper

	if ($echo) {
		echo $return;
	}
	return $return;
}
