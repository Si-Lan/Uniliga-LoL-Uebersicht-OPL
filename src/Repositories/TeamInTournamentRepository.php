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
	protected static array $ALL_DATA_KEYS = ["OPL_ID_team","OPL_ID_tournament","champs_played","champs_banned","champs_played_against","champs_banned_against","games_played","games_won","avg_win_time","name","dir_key"];
	protected static array $REQUIRED_DATA_KEYS = ["OPL_ID_team","OPL_ID_tournament"];
	/**
	 * @var array<int,TeamInTournament> $cache
	 */
	private array $cache = [];

	public function __construct() {
		parent::__construct();
		$this->teamRepo = new TeamRepository();
		$this->tournamentRepo = new TournamentRepository();
	}

	public function mapToEntity(array $data, ?Team $team=null, ?Tournament $tournament=null): TeamInTournament {
		$data = $this->normalizeData($data);
		if (is_null($team)) {
			$team = $this->teamRepo->findById($data['OPL_ID_team']);
		}
		if (is_null($tournament)) {
			$tournament = $this->tournamentRepo->findById($data['OPL_ID_tournament']);
		}
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
			logoHistoryDir: $this->intOrNull($data["dir_key"]),
			nameInTournament: $this->stringOrNull($data["name"])
		);
	}

	private function findInternal(int $teamId, int $tournamentId, ?Team $team=null, ?Tournament $tournament=null): ?TeamInTournament {
		$cacheKey = $teamId."_".$tournamentId;
		if (isset($this->cache[$cacheKey])) {
			return $this->cache[$cacheKey];
		}

		$query = '
			SELECT stit.*, tnh.name, tlh.dir_key
			FROM teams t
        		LEFT JOIN stats_teams_in_tournaments stit
                	ON t.OPL_ID = stit.OPL_ID_team AND stit.OPL_ID_tournament = ?
	        	LEFT JOIN tournaments tr
					ON tr.OPL_ID = ?
				LEFT JOIN team_logo_history tlh
					ON t.OPL_ID = tlh.OPL_ID_team AND (tlh.update_time < tr.dateEnd OR tr.dateEnd IS NULL)
				LEFT JOIN team_name_history tnh
					ON t.OPL_ID = tnh.OPL_ID_team AND (tnh.update_time < tr.dateEnd OR tr.dateEnd IS NULL)
			WHERE t.OPL_ID = ?
			ORDER BY tlh.update_time DESC, tnh.update_time DESC
			LIMIT 1';
		$result = $this->dbcn->execute_query($query, [$tournamentId,$tournamentId,$teamId]);
		$data = $result->fetch_assoc();

		if (is_null($data["OPL_ID_team"]) || is_null($data["OPL_ID_tournament"])) {
			$data["OPL_ID_team"] = $teamId;
			$data["OPL_ID_tournament"] = $tournamentId;
		}

		$teamInTournament = $data ? $this->mapToEntity($data, team: $team, tournament: $tournament) : null;
		$this->cache[$cacheKey] = $teamInTournament;

		return $teamInTournament;
	}

	public function findByTeamIdAndTournamentId(int $teamId, int $tournamentId): ?TeamInTournament {
		return $this->findInternal($teamId, $tournamentId);
	}
	public function findByTeamAndTournament(Team $team, Tournament $tournament): ?TeamInTournament {
		return $this->findInternal($team->id, $tournament->id, $team, $tournament);
	}
}