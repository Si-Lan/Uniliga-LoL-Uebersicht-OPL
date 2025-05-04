<?php

namespace App\Repositories;

use App\Entities\PlayerSeasonRank;
use App\Entities\RankedSplit;
use App\Utilities\DataParsingHelpers;

class PlayerSeasonRankRepository extends AbstractRepository {
	use DataParsingHelpers;

	private RankedSplitRepository $rankedSplitRepo;
	protected static array $ALL_DATA_KEYS = ["OPL_ID_player","season","split","rank_tier","rank_div","rank_LP"];
	protected static array $REQUIRED_DATA_KEYS = ["OPL_ID_player","season","split"];

	public function __construct() {
		parent::__construct();
		$this->rankedSplitRepo = new RankedSplitRepository();
	}

	public function mapToEntity(array $data, ?RankedSplit $rankedSplit = null): PlayerSeasonRank {
		$data = $this->normalizeData($data);
		if (is_null($rankedSplit)) {
			$rankedSplit = $this->rankedSplitRepo->findBySeasonAndSplit($data["season"],$data["split"]);
		}
		return new PlayerSeasonRank(
			playerId: (int) $data["OPL_ID_player"],
			season: (int) $data["season"],
			split: (int) $data["split"],
			rankedSplit: $rankedSplit,
			rankTier: $this->stringOrNull($data["rank_tier"]),
			rankDiv: $this->stringOrNull($data["rank_div"]),
			rankLp: $this->intOrNull($data["rank_LP"])
		);
	}

	public function findByPlayerIdAndSeasonAndSplit(int $playerId, string $season, string $split): ?PlayerSeasonRank {
		$result = $this->dbcn->execute_query("SELECT * FROM players_season_rank WHERE OPL_ID_player = ? AND season = ? AND split = ?",[$playerId, $season, $split]);
		$data = $result->fetch_assoc();

		return $data ? $this->mapToEntity($data) : null;
	}

	public function findByPlayerIdAndRankedSplit(int $playerId, RankedSplit $rankedSplit): ?PlayerSeasonRank {
		$result = $this->dbcn->execute_query("SELECT * FROM players_season_rank WHERE OPL_ID_player = ? AND season = ? AND split = ?",[$playerId, $rankedSplit->season, $rankedSplit->split]);
		$data = $result->fetch_assoc();

		return $data ? $this->mapToEntity($data, rankedSplit: $rankedSplit) : null;
	}
}