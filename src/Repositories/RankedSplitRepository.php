<?php

namespace App\Repositories;

use App\Database\DatabaseConnection;
use App\Entities\RankedSplit;
use App\Entities\Tournament;
use App\Utilities\DataParsingHelpers;

class RankedSplitRepository {
	use DataParsingHelpers;

	private \mysqli $dbcn;

	public function __construct() {
		$this->dbcn = DatabaseConnection::getConnection();
	}

	public function mapToEntity(array $data): RankedSplit {
		return new RankedSplit(
			season: (int) $data['season'],
			split: (int) $data['split'],
			dateStart: new \DateTimeImmutable($data['split_start']),
			dateEnd: $this->DateTimeImmutableOrNull($data['split_end']??null),
		);
	}

	public function findBySeasonAndSplit(int $season, ?int $split=null) : ?RankedSplit {
		$split = $split ?? 0;
		$result = $this->dbcn->execute_query("SELECT * FROM lol_ranked_splits WHERE season = ? AND split = ?", [$season, $split]);
		$data = $result->fetch_assoc();

		$rankedSplit = $data ? $this->mapToEntity($data) : null;

		return $rankedSplit;
	}

	public function findFirstSplitForTournament(Tournament $tournament) : ?RankedSplit {
		return $this->findBySeasonAndSplit($tournament->rankedSeason, $tournament->rankedSplit);
	}

	public function findNextSplit(RankedSplit $rankedSplit) : ?RankedSplit {
		$result = $this->dbcn->execute_query("SELECT * FROM lol_ranked_splits WHERE season > ? OR (season = ? AND split > ?) ORDER BY season, split LIMIT 1",[$rankedSplit->season,$rankedSplit->season,$rankedSplit->split]);
		$data = $result->fetch_assoc();

		$rankedSplit = $data ? $this->mapToEntity($data) : null;

		return $rankedSplit;
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