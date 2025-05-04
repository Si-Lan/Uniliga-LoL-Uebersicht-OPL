<?php

namespace App\Repositories;

use App\Database\DatabaseConnection;
use App\Entities\Player;
use App\Entities\Team;
use App\Entities\PlayerInTeamInTournament;

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

		if (!$playerdata) return null;

		$playerRepo = new PlayerRepository();
		$player = $playerRepo->createEntityFromData($playerdata);

		$teamRepo = new TeamRepository();
		$team = $teamRepo->findById($teamId);

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