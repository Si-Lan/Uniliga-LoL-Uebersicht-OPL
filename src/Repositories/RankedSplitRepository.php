<?php

namespace App\Repositories;

use App\Entities\RankedSplit;
use App\Entities\Tournament;
use App\Utilities\DataParsingHelpers;

class RankedSplitRepository extends AbstractRepository {
	use DataParsingHelpers;

	protected static array $ALL_DATA_KEYS = ["season","split","split_start","split_end"];
	protected static array $REQUIRED_DATA_KEYS = ["season","split","split_start"];

	public function mapToEntity(array $data): RankedSplit {
		$data = $this->normalizeData($data);
		return new RankedSplit(
			season: (int) $data['season'],
			split: (int) $data['split'],
			dateStart: new \DateTimeImmutable($data['split_start']),
			dateEnd: $this->DateTimeImmutableOrNull($data['split_end']),
		);
	}

	public function findBySeasonAndSplit(int $season, ?int $split=null) : ?RankedSplit {
		$split = $split ?? 0;
		$result = $this->dbcn->execute_query("SELECT * FROM lol_ranked_splits WHERE season = ? AND split = ?", [$season, $split]);
		$data = $result->fetch_assoc();

		return $data ? $this->mapToEntity($data) : null;
	}

	public function findFirstSplitForTournament(Tournament $tournament) : ?RankedSplit {
		return $this->findBySeasonAndSplit($tournament->rankedSeason, $tournament->rankedSplit);
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
}