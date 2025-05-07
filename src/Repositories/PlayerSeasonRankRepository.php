<?php

namespace App\Repositories;

use App\Entities\Player;
use App\Entities\PlayerSeasonRank;
use App\Entities\RankedSplit;
use App\Entities\ValueObjects\RankForPlayer;
use App\Utilities\DataParsingHelpers;

class PlayerSeasonRankRepository extends AbstractRepository {
	use DataParsingHelpers;

	private PlayerRepository $playerRepo;
	private RankedSplitRepository $rankedSplitRepo;
	protected static array $ALL_DATA_KEYS = ["OPL_ID_player","season","split","rank_tier","rank_div","rank_LP"];
	protected static array $REQUIRED_DATA_KEYS = ["OPL_ID_player","season","split"];

	public function __construct() {
		parent::__construct();
		$this->playerRepo = new PlayerRepository();
		$this->rankedSplitRepo = new RankedSplitRepository();
	}

	public function mapToEntity(array $data, ?Player $player = null, ?RankedSplit $rankedSplit = null): PlayerSeasonRank {
		$data = $this->normalizeData($data);
		if (is_null($player)) {
			$player = $this->playerRepo->findById($data['OPL_ID_player']);
		}
		if (is_null($rankedSplit)) {
			$rankedSplit = $this->rankedSplitRepo->findBySeasonAndSplit($data["season"],$data["split"]);
		}
		return new PlayerSeasonRank(
			player: $player,
			rankedSplit: $rankedSplit,
			rank: new RankForPlayer(
				$this->stringOrNull($data["rank_tier"]),
				$this->stringOrNull($data["rank_div"]),
				$this->intOrNull($data["rank_LP"])
			)
		);
	}

	/**
	 * @param Player|int $player Player-Objekt oder Spieler-ID
	 * @param string|RankedSplit $seasonOrRankedSplit Season oder RankedSplit-Objekt
	 * @param string|null $split Split (nur notwendig, wenn Season als String übergeben wird)
	 * @return PlayerSeasonRank|null
	 */
	public function findPlayerSeasonRank(Player|int $player, string|RankedSplit $seasonOrRankedSplit, ?string $split = null): ?PlayerSeasonRank {
		$playerId = $player instanceof Player ? $player->id : $player;
		$playerObj = $player instanceof Player ? $player : null;

		if ($seasonOrRankedSplit instanceof RankedSplit) {
			$season = $seasonOrRankedSplit->season;
			$split = $seasonOrRankedSplit->split;
			$rankedSplitObj = $seasonOrRankedSplit;
		} else {
			$season = $seasonOrRankedSplit;
			$rankedSplitObj = null;
			if ($split === null) {
				throw new \InvalidArgumentException("Split must be provided when no RankedSplit is given.");
			}
		}

		$result = $this->dbcn->execute_query("SELECT * FROM players_season_rank WHERE OPL_ID_player = ? AND season = ? AND split = ?",[$playerId, $season, $split]);
		$data = $result->fetch_assoc();

		return $data ? $this->mapToEntity($data, player: $playerObj, rankedSplit: $rankedSplitObj) : null;
	}
}