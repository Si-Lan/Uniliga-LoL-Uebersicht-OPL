<?php

namespace App\Domain\Repositories;

use App\Core\Logger;
use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Entities\RankedSplit;
use App\Domain\Entities\Tournament;
use App\Domain\Enums\SaveResult;

class RankedSplitRepository extends AbstractRepository {
	use DataParsingHelpers;

	protected static array $ALL_DATA_KEYS = ["season","split","split_start","split_end"];
	protected static array $REQUIRED_DATA_KEYS = ["season","split","split_start"];

	private array $cache = [];

	public function mapToEntity(array $data): RankedSplit {
		$data = $this->normalizeData($data);
		return new RankedSplit(
			season: (int) $data['season'],
			split: (int) $data['split'],
			dateStart: new \DateTimeImmutable($data['split_start']),
			dateEnd: $this->DateTimeImmutableOrNull($data['split_end']),
		);
	}

	public function findBySeasonAndSplit(int $season, ?int $split=null, bool $ignoreCache = false) : ?RankedSplit {
		$cacheKey = $season."_".$split;
		if (isset($this->cache[$cacheKey]) && !$ignoreCache) {
			return $this->cache[$cacheKey];
		}
		$split = $split ?? 0;
		$result = $this->dbcn->execute_query("SELECT * FROM lol_ranked_splits WHERE season = ? AND split = ?", [$season, $split]);
		$data = $result->fetch_assoc();

		$rankedSplit = $data ? $this->mapToEntity($data) : null;
		if (!$ignoreCache) $this->cache[$cacheKey] = $rankedSplit;

		return $rankedSplit;
	}

	public function findAll(): array {
		$query = "SELECT * FROM lol_ranked_splits";
		$result = $this->dbcn->execute_query($query);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$rankedSplits = [];
		foreach ($data as $rankedSplitData) {
			$rankedSplits[] = $this->mapToEntity($rankedSplitData);
		}
		return $rankedSplits;
	}

	public function findAllByTournamentId(int $tournamentId) : array {
		$query = "SELECT lrs.* FROM tournaments_in_ranked_splits tirs LEFT JOIN lol_ranked_splits lrs ON tirs.season = lrs.season AND tirs.split = lrs.split WHERE tirs.OPL_ID_tournament = ? ORDER BY lrs.season, lrs.split";
		$result = $this->dbcn->execute_query($query, [$tournamentId]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$rankedSplits = [];
		foreach ($data as $rankedSplitData) {
			$rankedSplits[] = $this->mapToEntity($rankedSplitData);
		}
		return $rankedSplits;
	}

	public function findFirstSplitForTournament(Tournament $tournament) : ?RankedSplit {
		return count($tournament->rankedSplits) > 0 ? $this->findBySeasonAndSplit($tournament->rankedSplits[0]->season, $tournament->rankedSplits[0]->split) : null;
	}

	public function findNextSplit(RankedSplit $rankedSplit) : ?RankedSplit {
		$result = $this->dbcn->execute_query("SELECT * FROM lol_ranked_splits WHERE season > ? OR (season = ? AND split > ?) ORDER BY season, split LIMIT 1",[$rankedSplit->season,$rankedSplit->season,$rankedSplit->split]);
		$data = $result->fetch_assoc();

		return $data ? $this->mapToEntity($data) : null;
	}
	public function findNextSplitForTournament(Tournament $tournament) : ?RankedSplit {
		$firstSplit = $this->findFirstSplitForTournament($tournament);
		$nextSplit = $this->findNextSplit($firstSplit);

		if (($tournament->dateEnd == null) || $nextSplit->dateStart < $tournament->dateEnd) {
			return $nextSplit;
		} else {
			return null;
		}
	}

	public function findSelectedSplitForTournament(Tournament $tournament) : ?RankedSplit {
		if (!isset($_COOKIE["tournament_ranked_splits"])) {
			// Keine Split-Auswahl gespeichert, nehme ersten Split des Turniers
			$current_split = $this->findFirstSplitForTournament($tournament);
		} else {
			$selectedSplits = json_decode($_COOKIE["tournament_ranked_splits"], true) ?? [];
			if (array_key_exists($tournament->id, $selectedSplits)) {
				$seasonAndSplit = explode("-", $selectedSplits[$tournament->id]);
				$current_split = $this->findBySeasonAndSplit($seasonAndSplit[0], $seasonAndSplit[1]);
			} else {
				// Keine Split-Auswahl für aktuelles Turnier gespeichert, nehme ersten Split des Turniers
				$current_split = $this->findFirstSplitForTournament($tournament);
			}
		}

		return $current_split;
	}

	public function rankedSplitExists(int $season, int $split) : bool {
		$result = $this->dbcn->execute_query("SELECT * FROM lol_ranked_splits WHERE season = ? AND split = ?", [$season, $split]);
		return $result->num_rows > 0;
	}

	public function mapEntityToData(RankedSplit $rankedSplit): array {
		return [
			"season" => $rankedSplit->season,
			"split" => $rankedSplit->split,
			"split_start" => $rankedSplit->dateStart->format("Y-m-d"),
			"split_end" => $rankedSplit->dateEnd?->format("Y-m-d") ?? null,
		];
	}

	private function insert(RankedSplit $rankedSplit): void {
		$data = $this->mapEntityToData($rankedSplit);
		$columns = implode(",", array_keys($data));
		$placeholders = implode(",", array_fill(0, count($data), "?"));
		$values = array_values($data);

		/** @noinspection SqlInsertValues */
		$query = "INSERT INTO lol_ranked_splits ($columns) VALUES ($placeholders)";
		$this->dbcn->execute_query($query, $values);
		$cacheKey = $rankedSplit->season."_".$rankedSplit->split;
		unset($this->cache[$cacheKey]);
	}
	private function update(RankedSplit $rankedSplit): array {
		$existingRankedSplit = $this->findBySeasonAndSplit($rankedSplit->season, $rankedSplit->split, ignoreCache: true);

		$dataNew = $this->mapEntityToData($rankedSplit);
		$dataOld = $this->mapEntityToData($existingRankedSplit);
		$dataChanged = array_diff_assoc($dataNew, $dataOld);
		$dataPrevious = array_diff_assoc($dataOld, $dataNew);

		if (count($dataChanged) == 0) {
			return ['result' => SaveResult::NOT_CHANGED];
		}

		$set = implode(",", array_map(fn($key) => "$key = ?", array_keys($dataChanged)));
		$values = array_values($dataChanged);

		$query = "UPDATE lol_ranked_splits SET $set WHERE season = ? AND split = ?";
		$this->dbcn->execute_query($query, [...$values, $rankedSplit->season, $rankedSplit->split]);

		$cacheKey = $rankedSplit->season."_".$rankedSplit->split;
		unset($this->cache[$cacheKey]);

		return ['result' => SaveResult::UPDATED, 'changes' => $dataChanged, 'previous' => $dataPrevious];
	}
	public function save(RankedSplit $rankedSplit): array {
		try {
			if ($this->rankedSplitExists($rankedSplit->season, $rankedSplit->split)) {
				$saveResult = $this->update($rankedSplit);
			} else {
				$this->insert($rankedSplit);
				$saveResult = ['result'=>SaveResult::INSERTED];
			}
		} catch (\Throwable $e) {
			Logger::log('db', "Fehler beim Speichern von RankedSplits: " . $e->getMessage() . "\n" . $e->getTraceAsString());
			$saveResult = ['result'=>SaveResult::FAILED];
		}
		$saveResult['rankedSplit'] = $this->findBySeasonAndSplit($rankedSplit->season, $rankedSplit->split);
		return $saveResult;
	}
	public function delete(RankedSplit $rankedSplit): bool {
		if (!$this->rankedSplitExists($rankedSplit->season, $rankedSplit->split)) {
			return false;
		}
		$query = "DELETE FROM lol_ranked_splits WHERE season = ? AND split = ?";
		return $this->dbcn->execute_query($query, [$rankedSplit->season, $rankedSplit->split]);
	}
}