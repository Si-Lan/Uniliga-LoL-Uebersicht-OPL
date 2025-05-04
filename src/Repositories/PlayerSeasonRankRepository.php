<?php

namespace App\Repositories;

use App\Database\DatabaseConnection;
use App\Entities\PlayerSeasonRank;
use App\Utilities\DataParsingHelpers;

class PlayerSeasonRankRepository {
	use DataParsingHelpers;

	private \mysqli $dbcn;

	public function __construct() {
		$this->dbcn = DatabaseConnection::getConnection();
	}

	public function findByPlayerAndSeasonAndSplit(int $playerId, string $season, string $split): ?PlayerSeasonRank {
		$result = $this->dbcn->execute_query("SELECT * FROM players_season_rank WHERE OPL_ID_player = ? AND season = ? AND split = ?",[$playerId, $season, $split]);
		$data = $result->fetch_assoc();

		$rankedSplitRepo = new RankedSplitRepository();
		$rankedSplit = $rankedSplitRepo->findBySeasonAndSplit($season, $split);

		$playerSeasonRank = $data ? new PlayerSeasonRank(
			playerId: (int) $data["OPL_ID_player"],
			season: (int) $data["season"],
			split: (int) $data["split"],
			rankedSplit: $rankedSplit,
			rankTier: (string) $data["rank_tier"],
			rankDiv: (string) $data["rank_div"],
			rankLp: (int) $data["rank_LP"]
		) : null;

		return $playerSeasonRank;
	}
}