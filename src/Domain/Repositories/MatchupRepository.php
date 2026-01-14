<?php

namespace App\Domain\Repositories;

use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Entities\Matchup;
use App\Domain\Entities\TeamInTournament;
use App\Domain\Entities\Tournament;
use App\Domain\Enums\SaveResult;
use App\Domain\Factories\MatchupFactory;
use App\Domain\ValueObjects\RepositorySaveResult;

class MatchupRepository extends AbstractRepository {
	use DataParsingHelpers;
	private MatchupFactory $factory;
	protected static array $OPL_DATA_KEYS = ["OPL_ID","OPL_ID_tournament","OPL_ID_team1","OPL_ID_team2","plannedDate","playday","bestOf"];

	public function __construct() {
		parent::__construct();
		$this->factory = new MatchupFactory();
	}

	public function findById(int $matchupId): ?Matchup {
		$query = "SELECT * FROM matchups WHERE OPL_ID = ?";
		$result = $this->dbcn->execute_query($query,[$matchupId]);
		$data = $result->fetch_assoc();

		return $data ? $this->factory->createFromDbData($data) : null;
	}

	/**
	 * @return array<Matchup>
	 */
	private function findAllInternalByQuery(string $query, array $queryParams, ?Tournament $tournamentStage=null, ?TeamInTournament $teamInTournament=null): array {
		$result = $this->dbcn->execute_query($query, $queryParams);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$matchups = [];
		foreach ($data as $matchupData) {
			$team1 = ($teamInTournament?->team->id == $matchupData["OPL_ID_team1"]) ? $teamInTournament : null;
			$team2 = ($teamInTournament?->team->id == $matchupData["OPL_ID_team2"]) ? $teamInTournament : null;
			$matchups[] = $this->factory->createFromDbData($matchupData, $tournamentStage, $team1, $team2);
		}

		return $matchups;
	}

	/**
	 * @return array<Matchup>
	 */
	public function findAllByRootTournamentAndTeam(Tournament $tournament, TeamInTournament $team): array {
		$query = '
			SELECT m.*
			FROM matchups m
			    LEFT JOIN tournaments t
			        ON m.OPL_ID_tournament = t.OPL_ID
			WHERE t.OPL_ID_top_parent = ? AND (OPL_ID_team1 = ? OR OPL_ID_team2 = ?)';
		$queryParams = [$tournament->id, $team->team->id, $team->team->id];

		return $this->findAllInternalByQuery($query, $queryParams, teamInTournament: $team);
	}
	/**
	 * @return array<Matchup>
	 */
	public function findAllByTournamentStageAndTeam(Tournament $tournamentStage, TeamInTournament $team): array {
		$query = 'SELECT * FROM matchups WHERE OPL_ID_tournament = ? AND (OPL_ID_team1 = ? OR OPL_ID_team2 = ?)';
		$queryParams = [$tournamentStage->id, $team->team->id, $team->team->id];

		return $this->findAllInternalByQuery($query, $queryParams, $tournamentStage, $team);
	}
	/**
	 * @return array<Matchup>
	 */
	public function findAllByTournamentStage(Tournament $tournamentStage, bool $unplayedOnly = false): array {
		$whereAdditional = $unplayedOnly ? 'AND played = 0' : '';
		$query = 'SELECT * FROM matchups WHERE OPL_ID_tournament = ? '.$whereAdditional;
		$queryParams = [$tournamentStage->id];
		return $this->findAllInternalByQuery($query, $queryParams, $tournamentStage);
	}
	/**
	 * @return array<Matchup>
	 */
	public function findAllWithATeamByTournamentStage(Tournament $tournamentStage): array {
		$query = '
			SELECT * 
			FROM matchups
			WHERE OPL_ID_tournament = ?
				AND NOT (
				    (OPL_ID_team1 IS NULL OR OPL_ID_team1 < 0)
					AND (OPL_ID_team2 IS NULL OR OPL_ID_team2 < 0)
				)';
		$queryParams = [$tournamentStage->id];
		return $this->findAllInternalByQuery($query, $queryParams, $tournamentStage);
	}
	/**
	 * @return array<Matchup>
	 */
	public function findAllByRootTournament(Tournament $rootTournament, bool $unplayedOnly = false): array {
		$whereAdditional = $unplayedOnly ? 'AND m.played = 0' : '';
		$query = '
			SELECT m.*
			FROM matchups m
			    LEFT JOIN tournaments t
			        ON m.OPL_ID_tournament = t.OPL_ID
			WHERE t.OPL_ID_top_parent = ? '
			.$whereAdditional;
		$queryParams = [$rootTournament->id];
		return $this->findAllInternalByQuery($query, $queryParams);
	}

	public function findAllByParentTournament(Tournament $parentTournament, bool $unplayedOnly = false): array {
		$whereAdditional = $unplayedOnly ? 'AND m.played = 0' : '';
		$query = '
			SELECT m.*
			FROM matchups m
			    LEFT JOIN tournaments t
			        ON m.OPL_ID_tournament = t.OPL_ID
			WHERE t.OPL_ID_parent = ? '
			.$whereAdditional;
		$queryParams = [$parentTournament->id];
		return $this->findAllInternalByQuery($query, $queryParams);
	}

	public function matchupExists(int $matchupId): bool {
		$query = "SELECT * FROM matchups WHERE OPL_ID = ?";
		$result = $this->dbcn->execute_query($query,[$matchupId]);
		return $result->num_rows > 0;
	}

	private function insert(Matchup $matchup, bool $fromOplData = false): void {
		$data = $this->factory->mapEntityToDbData($matchup);
		$columns = implode(",", array_keys($data));
		$placeholders = implode(",", array_fill(0, count($data), "?"));
		$values = array_values($data);

		/** @noinspection SqlInsertValues */
		$query = "INSERT INTO matchups ($columns) VALUES ($placeholders)";
		$this->dbcn->execute_query($query, $values);
	}

	private function update(Matchup $matchup, bool $fromOplData = false): RepositorySaveResult {
		$existingMatchup = $this->findById($matchup->id);

		$dataNew = $this->factory->mapEntityToDbData($matchup);
		$dataOld = $this->factory->mapEntityToDbData($existingMatchup);
		if ($fromOplData) {
			$dataNew = $this->filterKeysFromOpl($dataNew);
			$dataOld = $this->filterKeysFromOpl($dataOld);
		}
		$dataChanged = array_diff_assoc($dataNew, $dataOld);
		$dataPrevious = array_diff_assoc($dataOld, $dataNew);

		if (count($dataChanged) == 0) {
			return new RepositorySaveResult(SaveResult::NOT_CHANGED);
		}

		$set = implode(",", array_map(fn($key) => "$key = ?", array_keys($dataChanged)));
		$values = array_values($dataChanged);

		$query = "UPDATE matchups SET $set WHERE OPL_ID = ?";
		$this->dbcn->execute_query($query, [...$values, $matchup->id]);

		return new RepositorySaveResult(SaveResult::UPDATED, $dataChanged, $dataPrevious);
	}

	public function save(Matchup $matchup, bool $fromOplData = false): RepositorySaveResult {
		try {
			if ($this->matchupExists($matchup->id)) {
				$saveResult = $this->update($matchup, $fromOplData);
			} else {
				$this->insert($matchup, $fromOplData);
				$saveResult = new RepositorySaveResult(SaveResult::INSERTED);
			}
		} catch (\Throwable $e) {
			$this->logger->error("Fehler beim Speichern des Matchups: " . $e->getMessage().'\n'.$e->getTraceAsString());
			$saveResult = new RepositorySaveResult(SaveResult::FAILED);
		}
		$saveResult->entity = $this->findById($matchup->id);
		return $saveResult;
	}

	public function delete(Matchup $matchup): bool {
		if (!$this->matchupExists($matchup->id)) {
			return false;
		}
		return $this->dbcn->execute_query("DELETE FROM matchups WHERE OPL_ID = ?", [$matchup->id]);
	}
}