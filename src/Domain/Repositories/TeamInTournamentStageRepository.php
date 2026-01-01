<?php

namespace App\Domain\Repositories;

use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Entities\Team;
use App\Domain\Entities\TeamInTournament;
use App\Domain\Entities\TeamInTournamentStage;
use App\Domain\Entities\Tournament;
use App\Domain\Enums\SaveResult;

class TeamInTournamentStageRepository extends AbstractRepository {
	use DataParsingHelpers;

	private TeamRepository $teamRepo;
	private TournamentRepository $tournamentRepo;
	private TeamInTournamentRepository $teamInTournamentRepo;
	protected static array $ALL_DATA_KEYS = ["OPL_ID_team","OPL_ID_group","standing","played","wins","draws","losses","points","single_wins","single_losses"];
	protected static array $REQUIRED_DATA_KEYS = ["OPL_ID_team","OPL_ID_group"];
	/**
	 * @var array<int,TeamInTournamentStage> $cache
	 */
	private array $cache = [];

	public function __construct() {
		parent::__construct();
		$this->teamRepo = new TeamRepository();
		$this->tournamentRepo = new TournamentRepository();
		$this->teamInTournamentRepo = new TeamInTournamentRepository();
	}

	public function mapToEntity(array $data, ?Team $team=null, ?Tournament $tournamentStage=null, ?TeamInTournament $teamInRootTournament=null): TeamInTournamentStage {
		$data = $this->normalizeData($data);
		if (is_null($team)) {
			if ($this->teamRepo->dataHasAllFields($data)) {
				$team = $this->teamRepo->mapToEntity($data);
			} else {
				$team = $this->teamRepo->findById($data['OPL_ID_team']);
			}
		}
		if (is_null($tournamentStage)) {
			$tournamentStage = $this->tournamentRepo->findById($data['OPL_ID_group']);
		}
		if (is_null($teamInRootTournament)) {
			$teamInRootTournament = $this->teamInTournamentRepo->findByTeamAndTournament($team,$tournamentStage->rootTournament);
		}
		return new TeamInTournamentStage(
			team: $team,
			tournamentStage: $tournamentStage,
			teamInRootTournament: $teamInRootTournament,
			standing: $this->intOrNull($data['standing']),
			played: $this->intOrNull($data['played']),
			wins: $this->intOrNull($data['wins']),
			draws: $this->intOrNull($data['draws']),
			losses: $this->intOrNull($data['losses']),
			points: $this->intOrNull($data['points']),
			singleWins: $this->intOrNull($data['single_wins']),
			singleLosses: $this->intOrNull($data['single_losses']),
		);
	}

	public function findByTeamIdAndTournamentStageId(int $teamId, int $tournamentId, bool $ignoreCache = false): ?TeamInTournamentStage {
		$cacheKey = $teamId."_".$tournamentId;
		if (isset($this->cache[$cacheKey]) && !$ignoreCache) {
			return $this->cache[$cacheKey];
		}
		$query = '
			SELECT *
				FROM teams t
				LEFT JOIN teams_in_tournament_stages tits ON t.OPL_ID = tits.OPL_ID_team AND tits.OPL_ID_group = ?
				WHERE t.OPL_ID = ?';
		$result = $this->dbcn->execute_query($query, [$tournamentId, $teamId]);
		$data = $result->fetch_assoc();

		$teamInTournamentStage = $data ? $this->mapToEntity($data) : null;
		if (!$ignoreCache) $this->cache[$cacheKey] = $teamInTournamentStage;

		return $teamInTournamentStage;
	}

	/**
	 * @param Tournament $tournamentStage
	 * @param bool $orderByStandings
	 * @return array<TeamInTournamentStage>|null
	 */
	public function findAllByTournamentStage(Tournament $tournamentStage, bool $orderByStandings = true): ?array {
		$query = '
			SELECT *
			FROM teams t 
			    LEFT JOIN teams_in_tournament_stages tits ON t.OPL_ID = tits.OPL_ID_team
			WHERE tits.OPL_ID_group = ?
				AND t.OPL_ID > -1';
		if ($orderByStandings) {
			$query .= ' ORDER BY IF((standing=0 OR standing IS NULL), 1, 0), standing, single_wins DESC, single_losses, name';
		}
		$result = $this->dbcn->execute_query($query,[$tournamentStage->id]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$teams = [];
		foreach ($data as $team) {
			$teams[] = $this->mapToEntity($team, tournamentStage: $tournamentStage);
		}
		return $teams;
	}

	/**
	 * @param Tournament $tournament
	 * @return array<TeamInTournamentStage>
	 */
	public function findAllByRootTournament(Tournament $tournament): array {
		$query = '
			SELECT *
			FROM teams t 
			    LEFT JOIN teams_in_tournament_stages tits ON t.OPL_ID = tits.OPL_ID_team
			WHERE tits.OPL_ID_group IN (
			    SELECT OPL_ID FROM events_with_standings WHERE OPL_ID_top_parent = ?
			)
				AND t.OPL_ID > -1';
		$result = $this->dbcn->execute_query($query,[$tournament->id]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$teams = [];
		foreach ($data as $team) {
			$teams[] = $this->mapToEntity($team);
		}
		return $teams;
	}
	/**
	 * @param Tournament $tournament
	 * @return array<TeamInTournamentStage>
	 */
	public function findAllInGroupStageByRootTournament(Tournament $tournament): array {
		$query = '
			SELECT *
			FROM teams t 
			    LEFT JOIN teams_in_tournament_stages tits ON t.OPL_ID = tits.OPL_ID_team
			WHERE tits.OPL_ID_group IN (
			    SELECT OPL_ID FROM events_in_groupstage WHERE OPL_ID_top_parent = ?
			)
				AND t.OPL_ID > -1';
		$result = $this->dbcn->execute_query($query,[$tournament->id]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$teams = [];
		foreach ($data as $team) {
			$teams[] = $this->mapToEntity($team);
		}
		return $teams;
	}
	/**
	 * @param Tournament $tournament
	 * @return array<TeamInTournamentStage>
	 */
	public function findAllWildcardsByRootTournament(Tournament $tournament): array {
		$query = '
			SELECT *
			FROM teams t 
			    LEFT JOIN teams_in_tournament_stages tits ON t.OPL_ID = tits.OPL_ID_team
			WHERE tits.OPL_ID_group IN (
			    SELECT OPL_ID FROM events_wildcards WHERE OPL_ID_top_parent = ?
			)
				AND t.OPL_ID > -1';
		$result = $this->dbcn->execute_query($query,[$tournament->id]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$teams = [];
		foreach ($data as $team) {
			$teams[] = $this->mapToEntity($team);
		}
		return $teams;
	}

	/**
	 * @param Team $team
	 * @param Tournament $tournament
	 * @return array<TeamInTournamentStage>|null
	 */
	public function findAllByTeamAndTournament(Team $team, Tournament $tournament): ?array {
		$query = '
			SELECT *
			FROM teams t
			    LEFT JOIN teams_in_tournament_stages tits
			        ON t.OPL_ID = tits.OPL_ID_team
			WHERE t.OPL_ID = ?
			  AND tits.OPL_ID_group IN (SELECT OPL_ID FROM tournaments WHERE OPL_ID_top_parent = ?)';
		$result = $this->dbcn->execute_query($query, [$team->id, $tournament->id]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$stages = [];
		foreach ($data as $stage) {
			$stages[] = $this->mapToEntity($stage, team: $team);
		}
		return $stages;
	}
	/**
	 * @param TeamInTournament $teamInTournament
	 * @return array<TeamInTournamentStage>|null
	 */
	public function findAllbyTeamInTournament(TeamInTournament $teamInTournament): ?array {
		$query = '
			SELECT *
			FROM teams t
			    LEFT JOIN teams_in_tournament_stages tits
			        ON t.OPL_ID = tits.OPL_ID_team
			WHERE t.OPL_ID = ?
			  AND tits.OPL_ID_group IN (SELECT OPL_ID FROM tournaments WHERE OPL_ID_top_parent = ?)';
		$result = $this->dbcn->execute_query($query, [$teamInTournament->team->id, $teamInTournament->tournament->id]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$stages = [];
		foreach ($data as $stage) {
			$stages[] = $this->mapToEntity($stage, team: $teamInTournament->team, teamInRootTournament: $teamInTournament);
		}
		return $stages;
	}

	public function isTeaminTournamentStage(int $teamId, int $tournamentId): bool {
		$result = $this->dbcn->execute_query('SELECT * FROM teams_in_tournament_stages WHERE OPL_ID_team = ? AND OPL_ID_group = ?', [$teamId, $tournamentId]);
		return $result->num_rows > 0;
	}

	public function addTeamToTournamentStage(int $teamId, int $tournamentId): bool {
		if ($this->isTeaminTournamentStage($teamId, $tournamentId)) {
			return false;
		}
		$query = 'INSERT INTO teams_in_tournament_stages (OPL_ID_team, OPL_ID_group) VALUES (?, ?)';
		$this->dbcn->execute_query($query, [$teamId, $tournamentId]);
		return true;
	}

	public function removeTeamFromTournamentStage(int $teamId, int $tournamentId): bool {
		if (!$this->isTeaminTournamentStage($teamId, $tournamentId)) {
			return false;
		}
		$query = 'DELETE FROM teams_in_tournament_stages WHERE OPL_ID_team = ? AND OPL_ID_group = ?';
		$this->dbcn->execute_query($query, [$teamId, $tournamentId]);
		unset($this->cache[$teamId."_".$tournamentId]);
		return true;
	}

	public function mapEntityToData(TeamInTournamentStage $teamInTournamentStage): array {
		return [
			"OPL_ID_team" => $teamInTournamentStage->team->id,
			"OPL_ID_group" => $teamInTournamentStage->tournamentStage->id,
			"standing" => $teamInTournamentStage->standing,
			"played" => $teamInTournamentStage->played,
			"wins" => $teamInTournamentStage->wins,
			"draws" => $teamInTournamentStage->draws,
			"losses" => $teamInTournamentStage->losses,
			"points" => $teamInTournamentStage->points,
			"single_wins" => $teamInTournamentStage->singleWins,
			"single_losses" => $teamInTournamentStage->singleLosses
		];
	}

	private function update(TeamInTournamentStage $teamInTournamentStage): array {
		$existingTeamInTournamentStage = $this->findByTeamIdAndTournamentStageId($teamInTournamentStage->team->id, $teamInTournamentStage->tournamentStage->id);

		$dataNew = $this->mapEntityToData($teamInTournamentStage);
		$dataOld = $this->mapEntityToData($existingTeamInTournamentStage);
		$dataChanged = array_diff_assoc($dataNew, $dataOld);
		$dataPrevious = array_diff_assoc($dataOld, $dataNew);

		if (count($dataChanged) == 0) {
			return ['result' => SaveResult::NOT_CHANGED];
		}

		$set = implode(",", array_map(fn($key) => "$key = ?", array_keys($dataChanged)));
		$values = array_values($dataChanged);

		$query = "UPDATE teams_in_tournament_stages SET $set WHERE OPL_ID_team = ? AND OPL_ID_group = ?";
		$updated = $this->dbcn->execute_query($query, array_merge([...$values, $teamInTournamentStage->team->id, $teamInTournamentStage->tournamentStage->id]));
		unset($this->cache[$teamInTournamentStage->team->id."_".$teamInTournamentStage->tournamentStage->id]);

		$result = $updated ? SaveResult::UPDATED : SaveResult::FAILED;
		return ['result' => $result, 'changes' => $dataChanged, 'previous' => $dataPrevious];
	}

	public function save(TeamInTournamentStage $teamInTournamentStage): array {
		try {
			if ($this->isTeaminTournamentStage($teamInTournamentStage->team->id, $teamInTournamentStage->tournamentStage->id)) {
				$saveResult = $this->update($teamInTournamentStage);
			} else {
				throw new \Exception("Team is not in tournament");
			}
		} catch (\Throwable $e) {
			$this->logger->error("Fehler beim Speichern von TeamInTournamentStage: ".$e->getMessage()."\n".$e->getTraceAsString());
			$saveResult = ['result' => SaveResult::FAILED];
		}
		$saveResult['teamInTournamentStage'] = $this->findByTeamIdAndTournamentStageId($teamInTournamentStage->team->id, $teamInTournamentStage->tournamentStage->id);
		return $saveResult;
	}
}