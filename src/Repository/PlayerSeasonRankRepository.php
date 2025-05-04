<?php

namespace App\Repository;

use App\Database\DatabaseConnection;
use App\Entities\PlayerSeasonRank;
use App\Entities\RankedSplit;

class PlayerSeasonRankRepository {
	private \mysqli $dbcn;

	public function __construct() {
		$this->dbcn = DatabaseConnection::getConnection();
	}

	public function findByPlayerAndSeasonAndSplit(int $playerId, string $season, string $split): ?PlayerSeasonRank {
		$result = $this->dbcn->execute_query("SELECT * FROM players_season_rank WHERE OPL_ID_player = ? AND season = ? AND split = ?",[$playerId, $season, $split]);
		$data = $result->fetch_assoc();

		$result = $this->dbcn->execute_query("SELECT * FROM lol_ranked_splits WHERE season = ? AND split = ?", [$season, $split]);
		$splitData = $result->fetch_assoc();

		$rankedSplit = new RankedSplit(
			season: (int) $splitData['season'],
			split: (int) $splitData['split'],
			dateStart: new \DateTimeImmutable($splitData['split_start']),
			dateEnd: new \DateTimeImmutable($splitData['split_end']??""),
		);

		$playerSeasonRank = $data ? new PlayerSeasonRank(
			playerId: $data["OPL_ID_player"],
			season: $data["season"],
			split: $data["split"],
			rankedSplit: $rankedSplit,
			rankTier: $data["rank_tier"],
			rankDiv: $data["rank_div"],
			rankLp: $data["rank_LP"]
		) : null;

		return $playerSeasonRank;
	}
}