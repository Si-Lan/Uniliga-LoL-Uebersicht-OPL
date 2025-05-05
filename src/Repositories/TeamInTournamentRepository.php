<?php

namespace App\Repositories;

use App\Entities\Team;
use App\Entities\TeamInTournament;
use App\Entities\Tournament;
use App\Entities\ValueObjects\RankAverage;
use App\Utilities\DataParsingHelpers;

class TeamInTournamentRepository extends AbstractRepository {
	use DataParsingHelpers;

	private TeamRepository $teamRepo;
	private TournamentRepository $tournamentRepo;
	protected static array $ALL_DATA_KEYS = ["OPL_ID_team","OPL_ID_tournament","champs_played","champs_banned","champs_played_against","champs_banned_against","games_played","games_won","avg_win_time"];
	protected static array $REQUIRED_DATA_KEYS = ["OPL_ID_team","OPL_ID_tournament"];

	public function __construct() {
		parent::__construct();
		$this->teamRepo = new TeamRepository();
		$this->tournamentRepo = new TournamentRepository();
	}

	public function mapToEntity(array $data, ?Team $team=null, ?Tournament $tournament=null): TeamInTournament {
		if (is_null($team)) {
			$team = $this->teamRepo->findById($data['OPL_ID_team']);
		}
		if (is_null($tournament)) {
			$tournament = $this->tournamentRepo->findById($data['OPL_ID_tournament']);
		}
		$ranks = $this->findRanksInTournament($data['OPL_ID_team'], $data['OPL_ID_tournament']);
		return new TeamInTournament(
			team: $team,
			tournament: $tournament,
			champsPlayed: $this->decodeJsonOrNull($data['champs_played']),
			champsBanned: $this->decodeJsonOrNull($data['champs_banned']),
			champsPlayedAgainst: $this->decodeJsonOrNull($data['champs_played_against']),
			champsBannedAgainst: $this->decodeJsonOrNull($data['champs_banned_against']),
			gamesPlayed: $this->intOrNull($data['games_played']),
			gamesWon: $this->intOrNull($data['games_won']),
			avgWinTime: $this->intOrNull($data['avg_win_time']),
			ranks: $ranks
		);
	}

	/**
	 * @return array<RankAverage>
	 */
	private function findRanksInTournament(int $teamId, int $tournamentId): array {
		$query = '
			SELECT *
				FROM teams_tournament_rank
				WHERE OPL_ID_team = ? AND OPL_ID_tournament = ?
				ORDER BY second_ranked_split';
		$result = $this->dbcn->execute_query($query, [$teamId, $tournamentId]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$ranks = [];
		foreach ($data as $rank) {
			$ranks[] = new RankAverage(
				$this->stringOrNull($rank['avg_rank_tier']),
				$this->stringOrNull($rank['avg_rank_div']),
				$this->floatOrNull($rank['avg_rank_num'])
			);
		}
		return $ranks;
	}

	public function findByTeamIdAndTournamentId(int $teamId, int $tournamentId): ?TeamInTournament {
		$query = '
			SELECT *
				FROM stats_teams_in_tournaments
				WHERE OPL_ID_team = ? AND OPL_ID_tournament = ?';
		$result = $this->dbcn->execute_query($query, [$teamId, $tournamentId]);
		$data = $result->fetch_assoc();

		return $data ? $this->mapToEntity($data) : null;
	}
}