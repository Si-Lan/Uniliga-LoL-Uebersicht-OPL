<?php

namespace App\Domain\Repositories;

use App\Core\Logger;
use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Entities\RankedSplit;
use App\Domain\Entities\Tournament;
use App\Domain\Enums\EventType;
use App\Domain\Enums\SaveResult;

class TournamentRepository extends AbstractRepository {
	use DataParsingHelpers;
	private RankedSplitRepository $rankedSplitRepo;

	protected static array $ALL_DATA_KEYS = ["OPL_ID","OPL_ID_parent","OPL_ID_top_parent","name","split","season","eventType","format","number","numberRangeTo","dateStart","dateEnd","OPL_ID_logo","finished","deactivated","archived","ranked_season","ranked_split"];
	protected static array $REQUIRED_DATA_KEYS = ["OPL_ID","name"];
	private array $cache = [];

	public function __construct() {
		parent::__construct();
		$this->rankedSplitRepo = new RankedSplitRepository();
	}

	public function mapToEntity(array $data, ?Tournament $directParentTournament=null, ?Tournament $rootTournament=null, ?RankedSplit $rankedSplit=null, bool $newEntity = false): Tournament {
		$data = $this->normalizeData($data);

		$dataRootId = $this->intOrNull($data['OPL_ID_top_parent']);
		if (is_null($rootTournament)) {
			if (!is_null($dataRootId) && $data["eventType"] !== EventType::TOURNAMENT->value) {
				$rootTournament = $this->findById($dataRootId);
			}
		}

		$rankedSplit = !is_null($rootTournament) ? $rootTournament->rankedSplit : $rankedSplit;
		$dataRankedSeason = $this->stringOrNull($data['ranked_season']);
		if (is_null($rankedSplit) && !is_null($dataRankedSeason)) {
			$dataRankedSplit = $this->stringOrNull($data['ranked_split']);
			$rankedSplit = $this->rankedSplitRepo->findBySeasonAndSplit($dataRankedSeason, $dataRankedSplit);
		}

		$dataDirectParentId = $this->intOrNull($data['OPL_ID_parent']);
		if (is_null($directParentTournament)) {
			if (!is_null($dataDirectParentId) && $data["eventType"] !== EventType::TOURNAMENT->value) {
				if (!is_null($rootTournament) && $dataDirectParentId === $rootTournament->id) {
					$directParentTournament = $rootTournament;
				} else {
					$directParentTournament = $this->findById($dataDirectParentId, rootParent: $rootTournament);
				}
			}
		}

		$tournament = new Tournament(
			id: (int) $data['OPL_ID'],
			directParentTournament: $directParentTournament,
			rootTournament: $rootTournament,
			name: (string) $data['name'],
			split: $this->stringOrNull($data['split']),
			season: $this->intOrNull($data['season']),
			eventType: $this->EventTypeEnumOrNull($data['eventType']),
			format: $this->EventFormatEnumOrNull($data['format']),
			number: $this->stringOrNull($data['number']),
			numberRangeTo: $this->stringOrNull($data['numberRangeTo']),
			dateStart: $this->DateTimeImmutableOrNull($data['dateStart']),
			dateEnd: $this->DateTimeImmutableOrNull($data['dateEnd']),
			logoId: $this->intOrNull($data['OPL_ID_logo']),
			finished: (bool) $data['finished']??false,
			deactivated: (bool) $data['deactivated']??false,
			archived: (bool) $data['archived']??false,
			rankedSplit: $rankedSplit,
			userSelectedRankedSplit: null,
			mostCommonBestOf: null
		);

		if ($newEntity) return $tournament;

		if ($data["eventType"] !== EventType::TOURNAMENT->value && $rootTournament !== null) {
			$tournament->userSelectedRankedSplit = $rootTournament->userSelectedRankedSplit;
		} elseif ($data["eventType"] === EventType::TOURNAMENT->value) {
			$tournament->userSelectedRankedSplit = $this->rankedSplitRepo->findSelectedSplitForTournament($tournament);
		}
		if ($tournament->isEventWithStanding()) $mostCommonBestOf = $this->dbcn->execute_query("SELECT bestOf, SUM(bestOf) AS amount FROM matchups WHERE OPL_ID_tournament = ? GROUP BY bestOf ORDER BY amount DESC",[$data["OPL_ID"]])->fetch_column();
		$tournament->mostCommonBestOf = $this->intOrNull($mostCommonBestOf??null);
		return $tournament;
	}

	public function findById(int $tournamentId, ?Tournament $directParent=null, ?Tournament $rootParent=null) : ?Tournament {
		if (isset($this->cache[$tournamentId])) {
			return $this->cache[$tournamentId];
		}
		$result = $this->dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournamentId]);
		$data = $result->fetch_assoc();

		$tournament = $data ? $this->mapToEntity($data,$directParent,$rootParent) : null;
		$this->cache[$tournamentId] = $tournament;

		return $tournament;
	}

	public function findStandingsEventById(int $tournamentId, ?Tournament $directParent=null, ?Tournament $rootParent=null) : ?Tournament {
		$result = $this->dbcn->execute_query("SELECT * FROM events_with_standings WHERE OPL_ID = ?", [$tournamentId]);
		$data = $result->fetch_assoc();

		if ($data && isset($this->cache[$tournamentId])) {
			return $this->cache[$tournamentId];
		}

		$tournament = $data ? $this->mapToEntity($data,$directParent,$rootParent) : null;
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
		return $data !== null;
	}
	public function standingEventExists(int $tournamentId): bool {
		$result = $this->dbcn->execute_query("SELECT * FROM events_with_standings WHERE OPL_ID = ?", [$tournamentId]);
		$data = $result->fetch_assoc();
		return $data !== null;
	}

	/**
	 * @return array<Tournament>
	 */
	public function findAllTournaments(): array {
		$result = $this->dbcn->execute_query("SELECT * FROM tournaments");
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$tournaments = [];
		foreach ($data as $tournamentData) {
			$tournaments[] = $this->mapToEntity($tournamentData);
		}
		return $tournaments;
	}

	/**
	 * @return array<Tournament>
	 */
	public function findAllRootTournaments(): array {
		$query = "SELECT * FROM tournaments WHERE eventType = ?";
		$result = $this->dbcn->execute_query($query,[EventType::TOURNAMENT->value]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$tournaments = [];
		foreach ($data as $tournamentData) {
			$tournaments[] = $this->mapToEntity($tournamentData);
		}
		return $tournaments;
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

		$tournaments = [];
		foreach ($data as $tournamentData) {
			$tournaments[] = $this->mapToEntity($tournamentData, rootTournament: $rootTournament);
		}
		return $tournaments;
	}
	/**
	 * @param Tournament $parentTournament
	 * @param EventType $type
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

		$tournaments = [];
		foreach ($data as $tournamentData) {
			$tournaments[] = $this->mapToEntity($tournamentData, directParentTournament: $parentTournament);
		}
		return $tournaments;
	}

	/**
	 * @param Tournament $rootTournament
	 * @return array<Tournament>
	 */
	public function findAllGroupsByRootTournament(Tournament $rootTournament):array {
		$query = 'SELECT * FROM events_in_groupstage WHERE OPL_ID_top_parent = ? ORDER BY number';
		$result = $this->dbcn->execute_query($query,[$rootTournament->id]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$tournaments = [];
		foreach ($data as $tournamentData) {
			$tournaments[] = $this->mapToEntity($tournamentData, rootTournament: $rootTournament);
		}
		return $tournaments;
	}

	/**
	 * @param Tournament $rootTournament
	 * @return array<Tournament>
	 */
	public function findAllStandingEventsByRootTournament(Tournament $rootTournament):array {
		$query = 'SELECT * FROM events_with_standings WHERE OPL_ID_top_parent = ?';
		$result = $this->dbcn->execute_query($query,[$rootTournament->id]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$tournaments = [];
		foreach ($data as $tournamentData) {
			$tournaments[] = $this->mapToEntity($tournamentData, rootTournament: $rootTournament);
		}
		return $tournaments;
	}

	/**
	 * @param Tournament $parentTournament
	 * @return array<Tournament>
	 */
	public function findAllStandingEventsByParentTournament(Tournament $parentTournament):array {
		$query = 'SELECT * FROM events_with_standings WHERE OPL_ID_parent = ?';
		$result = $this->dbcn->execute_query($query,[$parentTournament->id]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$tournaments = [];
		foreach ($data as $tournamentData) {
			$tournaments[] = $this->mapToEntity($tournamentData, directParentTournament: $parentTournament);
		}
		return $tournaments;
	}

	public function findAllUnassignedTournaments():array {
		$query = 'SELECT * FROM tournaments WHERE (OPL_ID_parent IS NULL OR OPL_ID_top_parent IS NULL) AND eventType != ?';
		$result = $this->dbcn->execute_query($query,[EventType::TOURNAMENT->value]);
		$data = $result->fetch_all(MYSQLI_ASSOC);
		$tournaments = [];
		foreach ($data as $tournamentData) {
			$tournaments[] = $this->mapToEntity($tournamentData);
		}
		return $tournaments;
	}

	/**
	 * @return array<Tournament>
	 */
	public function findAllRunningRootTournaments():array {
		$tournaments = $this->findAllRootTournaments();
		return array_values(array_filter($tournaments, fn(Tournament $tournament) => $tournament->isRunning()));
	}


	public function mapEntityToData(Tournament $tournament): array {
		return [
			'OPL_ID' => $tournament->id,
			'OPL_ID_parent' => $tournament->directParentTournament?->id,
			'OPL_ID_top_parent' => $tournament->rootTournament?->id,
			'name' => $tournament->name,
			'split' => $tournament->split,
			'season' => $tournament->season,
			'eventType' => $tournament->eventType?->value,
			'format' => $tournament->format?->value,
			'number' => $tournament->number,
			'numberRangeTo' => $tournament->numberRangeTo,
			'dateStart' => $tournament->dateStart?->format('Y-m-d'),
			'dateEnd' => $tournament->dateEnd?->format('Y-m-d'),
			'OPL_ID_logo' => $tournament->logoId,
			'finished' => $tournament->finished ? 1 : 0,
			'deactivated' => $tournament->deactivated ? 1 : 0,
			'archived' => $tournament->archived ? 1 : 0,
			'ranked_season' => $tournament->rankedSplit?->season ?? null,
			'ranked_split' => $tournament->rankedSplit?->split ?? null
		];
	}
	private function insert(Tournament $tournament):void {
		$data = $this->mapEntityToData($tournament);
		$columns = implode(", ", array_keys($data));
		$placeholders = implode(", ", array_fill(0, count($data), "?"));
		$values = array_values($data);

		/** @noinspection SqlInsertValues */
		$query = "INSERT INTO tournaments ($columns) VALUES ($placeholders)";
		$this->dbcn->execute_query($query, $values);

		unset($this->cache[$tournament->id]);
	}
	private function update(Tournament $tournament):void {
		$data = $this->mapEntityToData($tournament);
		$assignments = implode(", ", array_map(fn($key) => "$key = ?", array_keys($data)));
		$values = array_values($data);

		$query = "UPDATE tournaments SET $assignments WHERE OPL_ID = ?";
		$this->dbcn->execute_query($query, [...$values, $tournament->id]);

		unset($this->cache[$tournament->id]);
	}
	/**
	 * @param \App\Domain\Entities\Tournament $tournament
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
			Logger::log('db', "Fehler beim Speichern von Turnier $tournament->id: " . $e->getMessage() . $e->getTraceAsString());
			return ['result'=>SaveResult::FAILED];
		}
	}

	public function createFromOplData(array $oplData): Tournament {
		$entityData = [
			'OPL_ID' => $oplData['ID'],
			'name' => $oplData['name'],
			'dateStart' => $this->DateTimeImmutableOrNull($oplData['start_on']['date']??null)?->format('Y-m-d'),
			'dateEnd' => $this->DateTimeImmutableOrNull($oplData['end_on']['date']??null)?->format('Y-m-d'),
		];
		$logo_url = $oplData["logo_array"]["background"] ?? null;
		$logo_id = ($logo_url != null) ? explode("/", $logo_url, -1) : null;
		$logo_id = ($logo_id != null) ? end($logo_id) : null;
		$entityData['OPL_ID_logo'] = $logo_id;

		$name_lower = strtolower($oplData['name']);

		$entityData['split'] = null;
		$possible_splits = ["winter", "sommer"];
		foreach ($possible_splits as $possible_split) {
			if (str_contains($name_lower, $possible_split)) {
				$entityData['split'] = $possible_split;
			}
		}

		$entityData['season'] = null;
		if (preg_match("/(?:winter|sommer)(?:season|saison)? *[0-9]*([0-9]{2})/",$name_lower,$season_match)) {
			$entityData['season'] = $season_match[1];
		}

		$entityData['eventType'] = EventType::fromName($name_lower)->value;;


		$entityData['number'] = null;
		$entityData['numberRangeTo'] = null;
		switch ($entityData['eventType']) {
			case EventType::LEAGUE->value:
			case EventType::WILDCARD->value:
			case EventType::PLAYOFFS->value:
				// Matcht: "Liga 1", "Liga 2-5", "Liga 1./2."
				if (preg_match('/\bliga\s*(\d)(?:\D+(\d))?/', $name_lower, $matches)) {
					$entityData['number'] = (int) $matches[1];
					$entityData['numberRangeTo'] = isset($matches[2]) ? (int) $matches[2] : null;
				}
				// Matcht: "1. Liga", "1./2. Liga"
				if (preg_match('/\b(\d+)(?:\D+(\d+))?\s*liga\b/', $name_lower, $matches)) {
					$entityData['number'] = (int) $matches[1];
					$entityData['numberRangeTo'] = isset($matches[2]) ? (int) $matches[2] : null;
				}
				break;

			case EventType::GROUP->value:
				// Matcht "Gruppe A" oder "Group A"
				if (preg_match('/\b(?:gruppe|group)\s+([a-z])/', $name_lower, $matches)) {
					$entityData['number'] = strtoupper($matches[1]);
				}
				break;
		}

		$entityData['OPL_ID_parent'] = null;
		$entityData['OPL_ID_top_parent'] = null;
		if (count($oplData['ancestors'])>0) {
			switch ($entityData['eventType']) {
				case EventType::LEAGUE->value:
				case EventType::WILDCARD->value:
				case EventType::PLAYOFFS->value:
					$rootId = min($oplData['ancestors']);
					if ($this->tournamentExists($rootId, EventType::TOURNAMENT)) {
						$entityData['OPL_ID_top_parent'] = $rootId;
						$entityData['OPL_ID_parent'] = $rootId;
					}
					break;
				case EventType::GROUP->value:
					foreach ($oplData['ancestors'] as $ancestorId) {
						if ($this->tournamentExists($ancestorId, EventType::TOURNAMENT)) {
							$entityData['OPL_ID_top_parent'] = $ancestorId;
						}
						if ($this->tournamentExists($ancestorId, EventType::LEAGUE)) {
							$entityData['OPL_ID_parent'] = $ancestorId;
						}
					}
					break;
			}
		}

		return $this->mapToEntity($entityData, newEntity: true);
	}
}