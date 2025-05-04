<?php

namespace App\Repositories;

use App\Database\DatabaseConnection;
use App\Entities\PlayerSeasonRank;
use App\Entities\RankedSplit;
use App\Utilities\DataParsingHelpers;

class PlayerSeasonRankRepository {
	use DataParsingHelpers;

	private \mysqli $dbcn;
	private RankedSplitRepository $rankedSplitRepo;

	public function __construct() {
		$this->dbcn = DatabaseConnection::getConnection();
		$this->rankedSplitRepo = new RankedSplitRepository();
	}

	public function mapToEntity(array $data, ?RankedSplit $rankedSplit = null): PlayerSeasonRank {
		if (is_null($rankedSplit)) {
			$rankedSplit = $this->rankedSplitRepo->findBySeasonAndSplit($data["season"],$data["split"]);
		}
		return new PlayerSeasonRank(
			playerId: (int) $data["OPL_ID_player"],
			season: (int) $data["season"],
			split: (int) $data["split"],
			rankedSplit: $rankedSplit,
			rankTier: (string) $data["rank_tier"],
			rankDiv: (string) $data["rank_div"],
			rankLp: (int) $data["rank_LP"]
		);
	}

	public function findByPlayerIdAndSeasonAndSplit(int $playerId, string $season, string $split): ?PlayerSeasonRank {
		$result = $this->dbcn->execute_query("SELECT * FROM players_season_rank WHERE OPL_ID_player = ? AND season = ? AND split = ?",[$playerId, $season, $split]);
		$data = $result->fetch_assoc();

		$playerSeasonRank = $data ? $this->mapToEntity($data) : null;

		return $playerSeasonRank;
	}

	public function findByPlayerIdAndRankedSplit(int $playerId, RankedSplit $rankedSplit): ?PlayerSeasonRank {
		$result = $this->dbcn->execute_query("SELECT * FROM players_season_rank WHERE OPL_ID_player = ? AND season = ? AND split = ?",[$playerId, $rankedSplit->season, $rankedSplit->split]);
		$data = $result->fetch_assoc();

		$playerSeasonRank = $data ? $this->mapToEntity($data, rankedSplit: $rankedSplit) : null;

		return $playerSeasonRank;
	}
}