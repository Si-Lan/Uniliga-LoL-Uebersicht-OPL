<?php

namespace App\Domain\Repositories;

use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Entities\Team;
use App\Domain\Entities\TeamInTournament;
use App\Domain\Entities\Tournament;

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
			gamesPlayed: $this->intOrZero($data['games_played']),
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
			SELECT 
			    ltnt.name,
			    ltlt.dir_key,
			    t.OPL_ID as OPL_ID_team,
			    tr.OPL_ID as OPL_ID_tournament,
			    stit.champs_played,
			    stit.champs_banned,
			    stit.champs_played_against,
			    stit.champs_banned_against,
			    stit.games_played,
			    stit.games_won,
    			stit.avg_win_time
			FROM teams t
			    JOIN tournaments tr
			    	ON tr.OPL_ID = ?
        		LEFT JOIN stats_teams_in_tournaments stit
                	ON t.OPL_ID = stit.OPL_ID_team AND stit.OPL_ID_tournament = tr.OPL_ID
			    LEFT JOIN latest_team_name_in_tournament ltnt
					ON t.OPL_ID = ltnt.OPL_ID_team AND tr.OPL_ID = ltnt.OPL_ID_tournament
				LEFT JOIN latest_team_logo_in_tournament ltlt
					ON t.OPL_ID = ltlt.OPL_ID_team AND tr.OPL_ID = ltlt.OPL_ID_tournament
			WHERE t.OPL_ID = ?';
		$result = $this->dbcn->execute_query($query, [$tournamentId,$teamId]);
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
	public function findByTeamIdAndTournament(int $teamId, Tournament $tournament): ?TeamInTournament {
		return $this->findInternal($teamId, $tournament->id, null, $tournament);
	}
	public function findByTeamAndTournamentId(Team $team, int $tournamentId): ?TeamInTournament {
		return $this->findInternal($team->id, $tournamentId, $team);
	}

	/**
	 * @param Team $team
	 * @return array<TeamInTournament>
	 */
	public function findAllByTeam(Team $team): array {
		$query = '
			SELECT
			ltnt.name,
			ltlt.dir_key,
			t.OPL_ID as OPL_ID_team,
			tr.OPL_ID as OPL_ID_tournament,
		    stit.champs_played,
   			stit.champs_banned,
		    stit.champs_played_against,
		    stit.champs_banned_against,
		    stit.games_played,
		    stit.games_won,
		    stit.avg_win_time
			FROM teams t
			    JOIN tournaments tr
			        ON tr.OPL_ID IN (SELECT OPL_ID_top_parent FROM teams_in_tournaments JOIN tournaments ON teams_in_tournaments.OPL_ID_group = tournaments.OPL_ID WHERE OPL_ID_team = t.OPL_ID)
			    LEFT JOIN stats_teams_in_tournaments stit ON t.OPL_ID = stit.OPL_ID_team AND tr.OPL_ID = stit.OPL_ID_tournament
			    LEFT JOIN latest_team_name_in_tournament ltnt ON t.OPL_ID = ltnt.OPL_ID_team AND tr.OPL_ID = ltnt.OPL_ID_tournament
			    LEFT JOIN latest_team_logo_in_tournament ltlt ON t.OPL_ID = ltlt.OPL_ID_team AND tr.OPL_ID = ltlt.OPL_ID_tournament
			WHERE t.OPL_ID = ?';
		$result = $this->dbcn->execute_query($query, [$team->id]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$teamInTournaments = [];
		foreach ($data as $teamInTournamentData) {
			$teamInTournaments[] = $this->mapToEntity($teamInTournamentData, team: $team);
		}
		return $teamInTournaments;
	}

	/**
	 * @param Tournament $tournament
	 * @return array<TeamInTournament>
	 */
	public function findAllByRootTournament(Tournament $tournament): array {
		$query = '
			SELECT
			ltnt.name,
			ltlt.dir_key,
			t.OPL_ID as OPL_ID_team,
			tr.OPL_ID as OPL_ID_tournament,
		    stit.champs_played,
   			stit.champs_banned,
		    stit.champs_played_against,
		    stit.champs_banned_against,
		    stit.games_played,
		    stit.games_won,
		    stit.avg_win_time
			FROM teams t
			    JOIN tournaments tr
			        ON tr.OPL_ID = ? AND tr.OPL_ID IN (SELECT OPL_ID_top_parent FROM teams_in_tournaments JOIN tournaments ON teams_in_tournaments.OPL_ID_group = tournaments.OPL_ID WHERE OPL_ID_team = t.OPL_ID)
			    LEFT JOIN stats_teams_in_tournaments stit ON t.OPL_ID = stit.OPL_ID_team AND tr.OPL_ID = stit.OPL_ID_tournament
			    LEFT JOIN latest_team_name_in_tournament ltnt ON t.OPL_ID = ltnt.OPL_ID_team AND tr.OPL_ID = ltnt.OPL_ID_tournament
			    LEFT JOIN latest_team_logo_in_tournament ltlt ON t.OPL_ID = ltlt.OPL_ID_team AND tr.OPL_ID = ltlt.OPL_ID_tournament
			WHERE t.OPL_ID > -1';
		$result = $this->dbcn->execute_query($query, [$tournament->id]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$teamInTournaments = [];
		foreach ($data as $teamInTournamentData) {
			$teamInTournaments[] = $this->mapToEntity($teamInTournamentData, tournament: $tournament);
		}
		return $teamInTournaments;
	}
	public function findAllByStage(Tournament $tournamentStage): array {
		$query = '
			SELECT
			ltnt.name,
			ltlt.dir_key,
			t.OPL_ID as OPL_ID_team,
			tr.OPL_ID as OPL_ID_tournament,
		    stit.champs_played,
   			stit.champs_banned,
		    stit.champs_played_against,
		    stit.champs_banned_against,
		    stit.games_played,
		    stit.games_won,
		    stit.avg_win_time
			FROM teams t
			    JOIN tournaments tr
			        ON tr.OPL_ID IN (SELECT OPL_ID_top_parent FROM teams_in_tournaments JOIN tournaments ON teams_in_tournaments.OPL_ID_group = tournaments.OPL_ID AND tournaments.OPL_ID = ? WHERE OPL_ID_team = t.OPL_ID)
			    LEFT JOIN stats_teams_in_tournaments stit ON t.OPL_ID = stit.OPL_ID_team AND tr.OPL_ID = stit.OPL_ID_tournament
			    LEFT JOIN latest_team_name_in_tournament ltnt ON t.OPL_ID = ltnt.OPL_ID_team AND tr.OPL_ID = ltnt.OPL_ID_tournament
			    LEFT JOIN latest_team_logo_in_tournament ltlt ON t.OPL_ID = ltlt.OPL_ID_team AND tr.OPL_ID = ltlt.OPL_ID_tournament
			WHERE t.OPL_ID > -1';
		$result = $this->dbcn->execute_query($query, [$tournamentStage->id]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$teamInTournaments = [];
		foreach ($data as $teamInTournamentData) {
			$teamInTournaments[] = $this->mapToEntity($teamInTournamentData, tournament: $tournamentStage->getRootTournament());
		}
		return $teamInTournaments;
	}
	public function findAllByParentTournament(Tournament $tournament): array {
		$query = '
			SELECT
			ltnt.name,
			ltlt.dir_key,
			t.OPL_ID as OPL_ID_team,
			tr.OPL_ID as OPL_ID_tournament,
		    stit.champs_played,
   			stit.champs_banned,
		    stit.champs_played_against,
		    stit.champs_banned_against,
		    stit.games_played,
		    stit.games_won,
		    stit.avg_win_time
			FROM teams t
			    JOIN tournaments tr
			        ON tr.OPL_ID IN (SELECT OPL_ID_top_parent FROM teams_in_tournaments JOIN tournaments ON teams_in_tournaments.OPL_ID_group = tournaments.OPL_ID AND tournaments.OPL_ID_parent = ? WHERE OPL_ID_team = t.OPL_ID)
			    LEFT JOIN stats_teams_in_tournaments stit ON t.OPL_ID = stit.OPL_ID_team AND tr.OPL_ID = stit.OPL_ID_tournament
			    LEFT JOIN latest_team_name_in_tournament ltnt ON t.OPL_ID = ltnt.OPL_ID_team AND tr.OPL_ID = ltnt.OPL_ID_tournament
			    LEFT JOIN latest_team_logo_in_tournament ltlt ON t.OPL_ID = ltlt.OPL_ID_team AND tr.OPL_ID = ltlt.OPL_ID_tournament
			WHERE t.OPL_ID > -1';
		$result = $this->dbcn->execute_query($query, [$tournament->id]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$teamInTournaments = [];
		foreach ($data as $teamInTournamentData) {
			$teamInTournaments[] = $this->mapToEntity($teamInTournamentData, tournament: $tournament);
		}
		return $teamInTournaments;
	}

	public function isTeamInRootTournament(int $teamId, int $tournamentId): bool {
		$query = '
			SELECT 
			    t.OPL_ID as OPL_ID_team,
			    tr.OPL_ID as OPL_ID_tournament
			FROM teams t
			    JOIN tournaments tr
			    	ON tr.OPL_ID = ? AND tr.OPL_ID IN (SELECT OPL_ID_top_parent FROM teams_in_tournaments JOIN tournaments ON teams_in_tournaments.OPL_ID_group = tournaments.OPL_ID WHERE OPL_ID_team = t.OPL_ID)
			WHERE t.OPL_ID = ?';
		$result = $this->dbcn->execute_query($query, [$tournamentId,$teamId]);
		return $result->num_rows > 0;
	}
}