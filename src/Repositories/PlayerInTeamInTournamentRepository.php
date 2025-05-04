<?php

namespace App\Repositories;

use App\Database\DatabaseConnection;
use App\Entities\Player;
use App\Entities\Team;
use App\Entities\PlayerInTeamInTournament;
use App\Utilities\DataParsingHelpers;

class PlayerInTeamInTournamentRepository {
	use DataParsingHelpers;

	private \mysqli $dbcn;
	private TeamRepository $teamRepo;
	private PlayerRepository $playerRepo;

	public function __construct() {
		$this->dbcn = DatabaseConnection::getConnection();
		$this->teamRepo = new TeamRepository();
		$this->playerRepo = new PlayerRepository();
	}

	public function mapToEntity(array $data, ?Player $player = null, ?Team $team = null): PlayerInTeamInTournament {
		if (is_null($player)) {
			$player = $this->playerRepo->mapToEntity($data);
		}
		if (is_null($team)) {
			$team = $this->teamRepo->findById($data['OPL_ID_team']??null);
		}
		return new PlayerInTeamInTournament(
			player: $player,
			team: $team,
			removed: (bool) $data['removed']??false,
			roles: $this->decodeJsonOrDefault($data['roles']??null,'{"top":0,"jungle":0,"middle":0,"bottom":0,"utility":0}'),
			champions: $this->decodeJsonOrDefault($data['champions']??null, "[]")
		);
	}

	public function findByPlayerIdAndTeamIdAndTournamentId(int $playerId, int $teamId, int $tournamentId): ?PlayerInTeamInTournament {
		$query = '
			SELECT *
				FROM players p
				JOIN players_in_teams_in_tournament pitt ON p.OPL_ID = pitt.OPL_ID_player AND pitt.OPL_ID_tournament = ? AND pitt.OPL_ID_team = ?
				LEFT JOIN stats_players_teams_tournaments spit ON p.OPL_ID = spit.OPL_ID_player AND pitt.OPL_ID_team = spit.OPL_ID_team AND pitt.OPL_ID_tournament = spit.OPL_ID_tournament
				WHERE p.OPL_ID = ?';
		$result = $this->dbcn->execute_query($query, [$tournamentId, $teamId, $playerId]);
		$playerdata = $result->fetch_assoc();

		$playerTT = $playerdata ? $this->mapToEntity($playerdata) : null;

		return $playerTT;
	}
}