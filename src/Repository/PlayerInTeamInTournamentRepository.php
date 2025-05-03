<?php

namespace App\Repository;

use App\Database\DatabaseConnection;
use App\Entity\Player;
use App\Entity\Team;
use App\Entity\PlayerInTeamInTournament;

class PlayerInTeamInTournamentRepository {
	private \mysqli $dbcn;

	public function __construct() {
		$this->dbcn = DatabaseConnection::getConnection();
	}

	public function findByPlayerAndTeamAndTournament(int $playerId, int $teamId, int $tournamentId): ?PlayerInTeamInTournament {
		$query = '
			SELECT *
				FROM players p
				JOIN players_in_teams_in_tournament pitt ON p.OPL_ID = pitt.OPL_ID_player AND pitt.OPL_ID_tournament = ? AND pitt.OPL_ID_team = ?
				LEFT JOIN stats_players_teams_tournaments spit ON p.OPL_ID = spit.OPL_ID_player AND pitt.OPL_ID_team = spit.OPL_ID_team AND pitt.OPL_ID_tournament = spit.OPL_ID_tournament
				WHERE p.OPL_ID = ?';
		$result = $this->dbcn->execute_query($query, [$tournamentId, $teamId, $playerId]);
		$playerdata = $result->fetch_assoc();

		$query = 'SELECT * FROM teams WHERE OPL_ID = ?';
		$result = $this->dbcn->execute_query($query, [$teamId]);
		$teamdata = $result->fetch_assoc();

		if (!$playerdata) return null;

		$player = new Player(
			id: $playerdata['OPL_ID'],
			name: $playerdata['name'],
			riotIdName: $playerdata['riotID_name'],
			riotIdTag: $playerdata['riotID_tag'],
			summonerName: $playerdata['summonerName'],
			summonerId: $playerdata['summonerID'],
			puuid: $playerdata['PUUID'],
			rankTier: $playerdata['rank_tier'],
			rankDiv: $playerdata['rank_div'],
			rankLp: $playerdata['rank_LP'],
			matchesGotten: json_decode($playerdata['matches_gotten']),
		);

		$team = new Team(
			id: $teamdata['OPL_ID'],
			name: $teamdata['name'],
			shortName: $teamdata['shortName'],
			logoUrl: $teamdata['OPL_logo_url'],
			logoId: $teamdata['OPL_ID_logo'],
			lastLogoDownload: $teamdata['last_logo_download'],
			avgRankTier: $teamdata['avg_rank_tier'],
			avgRankDiv: $teamdata['avg_rank_div'],
			avgRankNum: $teamdata['avg_rank_num'],
		);

		$playerTT = new PlayerInTeamInTournament(
			player: $player,
			team: $team,
			removed: $playerdata['removed'],
			roles: json_decode($playerdata['roles'], true),
			champions: json_decode($playerdata['champions'], true)
		);

		return $playerTT;
	}
}