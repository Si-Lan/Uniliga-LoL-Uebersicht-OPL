<?php

namespace App\Domain\Repositories;

use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Entities\Tournament;
use App\Domain\Enums\EventType;
use App\Domain\Enums\SaveResult;
use App\Domain\Factories\TournamentFactory;

class TournamentRepository extends AbstractRepository {
	use DataParsingHelpers;
	private TournamentFactory $factory;
	private RankedSplitRepository $rankedSplitRepo;
	/**
	 * @var array<int,Tournament>
	 */
	private array $cache = [];

	public function __construct() {
		parent::__construct();
		$this->factory = new TournamentFactory();
		$this->rankedSplitRepo = new RankedSplitRepository();
	}

	public function buildTournament(
		array $data,
		?Tournament $directParent = null,
		?Tournament $rootParent = null,
		array $rankedSplits=[],
		bool $newEntity = false
	): Tournament {
		$id = $this->intOrNull($data['OPL_ID']);
		$directParentId = $this->intOrNull($data['OPL_ID_parent']);
		$rootParentId = $this->intOrNull($data['OPL_ID_top_parent']);
		if ($rootParent === null && $rootParentId !== null && $id !== $rootParentId) {
			$rootParent = $this->findById($rootParentId);
		}
		if ($directParent === null && $directParentId !== null && $id !== $directParentId) {
			$directParent = $this->findById($directParentId,$rootParent,$rootParent);
		}
		$mostCommonBestOf = $this->findMostCommonBestOfById($id);

		return $this->factory->createFromDbData($data,$directParent,$rootParent,$mostCommonBestOf, $rankedSplits, $newEntity);
	}

	/**
	 * @return array<Tournament>
	 */
	private function buildTournamentArray(
		array $data,
		?Tournament $directParent = null,
		?Tournament $rootParent = null
	): array {
		$tournaments = [];
		foreach ($data as $tournamentData) {
			if (isset($this->cache[$tournamentData["OPL_ID"]])) {
				$tournaments[] = $this->cache[$tournamentData["OPL_ID"]];
				continue;
			}

			$tournament = $this->buildTournament($tournamentData,$directParent,$rootParent);
			$this->cache[$tournament->id] = $tournament;
			$tournaments[] = $tournament;
		}
		return $tournaments;
	}

	private function findMostCommonBestOfById(int $tournamentId): ?int {
		$result = $this->dbcn->execute_query("SELECT bestOf, SUM(bestOf) AS amount FROM matchups WHERE OPL_ID_tournament = ? GROUP BY bestOf ORDER BY amount DESC",[$tournamentId]);
		$data = $result->fetch_column();
		return $this->intOrNull($data);
	}

	public function findById(int $tournamentId, ?Tournament $directParent=null, ?Tournament $rootParent=null, bool $ignoreCache = false) : ?Tournament {
		if (isset($this->cache[$tournamentId]) && !$ignoreCache) {
			return $this->cache[$tournamentId];
		}
		$result = $this->dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournamentId]);
		$data = $result->fetch_assoc();

		if (!$data) return null;

		$tournament = $this->buildTournament($data);

		if (!$ignoreCache) $this->cache[$tournamentId] = $tournament;

		return $tournament;
	}

	public function findStandingsEventById(int $tournamentId, ?Tournament $directParent=null, ?Tournament $rootParent=null) : ?Tournament {
		$result = $this->dbcn->execute_query("SELECT * FROM events_with_standings WHERE OPL_ID = ?", [$tournamentId]);
		$data = $result->fetch_assoc();

		if (!$data) return null;

		if (isset($this->cache[$tournamentId])) {
			return $this->cache[$tournamentId];
		}

		$tournament = $this->buildTournament($data);

		$this->cache[$tournamentId] = $tournament;

		return $tournament;
	}

	public function tournamentExists(int $tournamentId, ?EventType $eventType=null): bool {
		if (is_null($eventType)) {
			$result = $this->dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournamentId]);
		} else {
			$result = $this->dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ? AND eventType = ?", [$tournamentId, $eventType->value]);
		}
		$data = $result->fetch_assoc();

		if (!$data && isset($this->cache[$tournamentId])) {
			if ($eventType === null || $this->cache[$tournamentId]->eventType === $eventType) {
				unset($this->cache[$tournamentId]);
			}
		}
		return $data !== null;
	}
	public function standingEventExists(int $tournamentId): bool {
		$result = $this->dbcn->execute_query("SELECT * FROM events_with_standings WHERE OPL_ID = ?", [$tournamentId]);
		$data = $result->fetch_assoc();

		if (!$data && isset($this->cache[$tournamentId]) && $this->cache[$tournamentId]->isEventWithStanding()) {
			unset($this->cache[$tournamentId]);
		}
		return $data !== null;
	}

	/**
	 * @return array<Tournament>
	 */
	public function findAllTournaments(): array {
		$result = $this->dbcn->execute_query("SELECT * FROM tournaments");
		$data = $result->fetch_all(MYSQLI_ASSOC);

		return $this->buildTournamentArray($data);
	}

	/**
	 * @return array<Tournament>
	 */
	public function findAllRootTournaments(): array {
		$query = "SELECT * FROM tournaments WHERE eventType = ?";
		$result = $this->dbcn->execute_query($query,[EventType::TOURNAMENT->value]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		return $this->buildTournamentArray($data);
	}

	/**
	 * @param Tournament $rootTournament
	 * @param EventType $type
	 * @return array<Tournament>
	 */
	public function findAllByRootTournamentAndType(Tournament $rootTournament, EventType $type):array {
		$query = 'SELECT * FROM tournaments WHERE OPL_ID_top_parent = ? AND eventType = ? ORDER BY number';
		$result = $this->dbcn->execute_query($query,[$rootTournament->id,$type->value]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		return $this->buildTournamentArray($data, rootParent: $rootTournament);
	}

	/**
	 * @param Tournament $parentTournament
	 * @param EventType|null $type
	 * @return array<Tournament>
	 */
	public function findAllByParentTournamentAndType(Tournament $parentTournament, ?EventType $type = null):array {
		if (is_null($type)) {
			$query = 'SELECT * FROM tournaments WHERE OPL_ID_parent = ? ORDER BY number';
			$result = $this->dbcn->execute_query($query,[$parentTournament->id]);
		} else {
			$query = 'SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = ? ORDER BY number';
			$result = $this->dbcn->execute_query($query,[$parentTournament->id,$type->value]);
		}
		$data = $result->fetch_all(MYSQLI_ASSOC);

		return $this->buildTournamentArray($data, directParent: $parentTournament, rootParent: $parentTournament->getRootTournament());
	}

	/**
	 * @param Tournament $rootTournament
	 * @return array<Tournament>
	 */
	public function findAllGroupsByRootTournament(Tournament $rootTournament):array {
		$query = 'SELECT * FROM events_in_groupstage WHERE OPL_ID_top_parent = ? ORDER BY number';
		$result = $this->dbcn->execute_query($query,[$rootTournament->id]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		return $this->buildTournamentArray($data, rootParent: $rootTournament);
	}

	/**
	 * @param Tournament $rootTournament
	 * @return array<Tournament>
	 */
	public function findAllStandingEventsByRootTournament(Tournament $rootTournament):array {
		$query = 'SELECT * FROM events_with_standings WHERE OPL_ID_top_parent = ?';
		$result = $this->dbcn->execute_query($query,[$rootTournament->id]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		return $this->buildTournamentArray($data, rootParent: $rootTournament);
	}

	/**
	 * @param Tournament $parentTournament
	 * @return array<Tournament>
	 */
	public function findAllStandingEventsByParentTournament(Tournament $parentTournament):array {
		$query = 'SELECT * FROM events_with_standings WHERE OPL_ID_parent = ?';
		$result = $this->dbcn->execute_query($query,[$parentTournament->id]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		return $this->buildTournamentArray($data, directParent: $parentTournament, rootParent: $parentTournament->getRootTournament());
	}

	public function findAllUnassignedTournaments():array {
		$query = 'SELECT * FROM tournaments WHERE (OPL_ID_parent IS NULL OR OPL_ID_top_parent IS NULL) AND eventType != ?';
		$result = $this->dbcn->execute_query($query,[EventType::TOURNAMENT->value]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		return $this->buildTournamentArray($data);
	}

	/**
	 * @return array<Tournament>
	 */
	public function findAllRunningRootTournaments():array {
		$tournaments = $this->findAllRootTournaments();
		return array_values(array_filter($tournaments, fn(Tournament $tournament) => $tournament->isRunning()));
	}

    public function findAllRootTournamentsInCurrentRankedSplit(): array {
        $tournaments = $this->findAllRootTournaments();
        $currentRankedSplits = $this->rankedSplitRepo->findCurrentSplits();
        if (count($currentRankedSplits) === 0) {
            return [];
        }
        $currentRankedSplit = $currentRankedSplits[0];
        $currentTournaments = array_values(array_filter($tournaments, function(Tournament $tournament) use ($currentRankedSplit) {
            foreach ($tournament->rankedSplits as $rankedSplit) {
                if ($rankedSplit->equals($currentRankedSplit)) {
                    return true;
                }
            }
            return false;
        }));

        return $currentTournaments;
    }


	private function insert(Tournament $tournament):void {
		$data = $this->factory->mapEntityToDbData($tournament);
		$columns = implode(", ", array_keys($data));
		$placeholders = implode(", ", array_fill(0, count($data), "?"));
		$values = array_values($data);

		/** @noinspection SqlInsertValues */
		$query = "INSERT INTO tournaments ($columns) VALUES ($placeholders)";
		$this->dbcn->execute_query($query, $values);

        $this->updateRankedsplits($tournament);

		unset($this->cache[$tournament->id]);
	}
	private function update(Tournament $tournament):void {
		$data = $this->factory->mapEntityToDbData($tournament);
		$assignments = implode(", ", array_map(fn($key) => "$key = ?", array_keys($data)));
		$values = array_values($data);

		$query = "UPDATE tournaments SET $assignments WHERE OPL_ID = ?";
		$this->dbcn->execute_query($query, [...$values, $tournament->id]);

		$this->updateRankedsplits($tournament);

		unset($this->cache[$tournament->id]);
	}
	/**
	 * @param Tournament $tournament
	 * @return array{'result': SaveResult, 'changes': array<string, mixed>}
	 */
	public function save(Tournament $tournament):array {
		try {
			$existingTournament = $this->findById($tournament->id);
			if ($existingTournament) {
				$changedData = $tournament->getDataDifference($existingTournament);
				if (count($changedData)===0) {
					return ['result'=>SaveResult::NOT_CHANGED];
				}
				$this->update($tournament);
				return ['result'=>SaveResult::UPDATED, 'changes'=>$changedData];
			} else {
				$this->insert($tournament);
				return ['result'=>SaveResult::INSERTED];
			}
		} catch (\Throwable $e) {
			$this->logger->error("Fehler beim Speichern von Turnier $tournament->id: " . $e->getMessage() . $e->getTraceAsString());
			return ['result'=>SaveResult::FAILED];
		}
	}

	private function updateRankedsplits(Tournament $tournament): array {
		$existingTournament = $this->findById($tournament->id, ignoreCache: true);
		$newRankedSplits = array_map(fn($rankedSplit) => $rankedSplit->getName(), $tournament->rankedSplits);
		$newRankedSplits = array_combine($newRankedSplits, $tournament->rankedSplits);
		$oldRankedSplits = array_map(fn($rankedSplit) => $rankedSplit->getName(), $existingTournament->rankedSplits);
		$oldRankedSplits = array_combine($oldRankedSplits, $existingTournament->rankedSplits);
		$rankedSplitsToAdd = array_values(array_diff(array_keys($newRankedSplits), array_keys($oldRankedSplits)));;
		$rankedSplitsToRemove = array_values(array_diff(array_keys($oldRankedSplits), array_keys($newRankedSplits)));

		$addQuery = "INSERT INTO tournaments_in_ranked_splits (OPL_ID_tournament, season, split) VALUES (?,?,?)";
		$removeQuery = "DELETE FROM tournaments_in_ranked_splits WHERE OPL_ID_tournament = ? AND season = ? AND split = ?";

		foreach ($rankedSplitsToAdd as $rankedSplit) {
			$this->dbcn->execute_query($addQuery, [$tournament->id, $newRankedSplits[$rankedSplit]->season, $newRankedSplits[$rankedSplit]->split]);
		}
		foreach ($rankedSplitsToRemove as $rankedSplit) {
			$this->dbcn->execute_query($removeQuery, [$tournament->id, $oldRankedSplits[$rankedSplit]->season, $oldRankedSplits[$rankedSplit]->split]);
		}

		$saveResult = (count($rankedSplitsToAdd) > 0 || count($rankedSplitsToRemove)) ? SaveResult::UPDATED : SaveResult::NOT_CHANGED;

		return ["saveResult" => $saveResult, "rankedSplitsAdded" => $rankedSplitsToAdd, "rankedSplitsRemoved" => $rankedSplitsToRemove];
	}
}