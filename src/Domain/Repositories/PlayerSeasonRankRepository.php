<?php

namespace App\Domain\Repositories;

use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Entities\Player;
use App\Domain\Entities\PlayerSeasonRank;
use App\Domain\Entities\RankedSplit;
use App\Domain\Entities\ValueObjects\RankForPlayer;
use App\Domain\Enums\SaveResult;
use App\Domain\ValueObjects\RepositorySaveResult;

class PlayerSeasonRankRepository extends AbstractRepository {
	use DataParsingHelpers;

	private PlayerRepository $playerRepo;
	private RankedSplitRepository $rankedSplitRepo;
	protected static array $ALL_DATA_KEYS = ["OPL_ID_player","season","split","rank_tier","rank_div","rank_LP"];
	protected static array $REQUIRED_DATA_KEYS = ["OPL_ID_player","season","split"];
	/**
	 * @var array<string,PlayerSeasonRank> $cache
	 */
	private array $cache = [];

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
    public function mapEntityToData(PlayerSeasonRank $playerSeasonRank): array {
        return [
            "OPL_ID_player" => $playerSeasonRank->player->id,
            "season" => $playerSeasonRank->rankedSplit->season,
            "split" => $playerSeasonRank->rankedSplit->split,
            "rank_tier" => $playerSeasonRank->rank?->rankTier,
            "rank_div" => $playerSeasonRank->rank?->rankDiv,
            "rank_LP" => $playerSeasonRank->rank?->rankLp
        ];
    }

	/**
	 * @param Player|int $player Player-Objekt oder Spieler-ID
	 * @param string|RankedSplit $seasonOrRankedSplit Season oder RankedSplit-Objekt
	 * @param string|null $split Split (nur notwendig, wenn Season als String übergeben wird)
	 * @return PlayerSeasonRank|null
	 */
	public function findPlayerSeasonRank(Player|int $player, string|RankedSplit $seasonOrRankedSplit, ?string $split = null, bool $ignoreCache = false): ?PlayerSeasonRank {
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

		$cacheKey = $playerId."_".$season."_".$split;
		if (isset($this->cache[$cacheKey]) && !$ignoreCache) {
			return $this->cache[$cacheKey];
		}

		$result = $this->dbcn->execute_query("SELECT * FROM players_season_rank WHERE OPL_ID_player = ? AND season = ? AND split = ?",[$playerId, $season, $split]);
		$data = $result->fetch_assoc();

		$playerSeasonRank = $data ? $this->mapToEntity($data, player: $playerObj, rankedSplit: $rankedSplitObj) : null;
		if (!$ignoreCache) $this->cache[$cacheKey] = $playerSeasonRank;

		return $playerSeasonRank;
	}

    /**
     * @param Player $player
     * @return PlayerSeasonRank|null
     */
    public function findCurrentSeasonRankForPlayer(Player $player): ?PlayerSeasonRank {
        $currentRankedSplits = $this->rankedSplitRepo->findCurrentSplits();
        if (count($currentRankedSplits) === 0) {
            return null;
        }
        $currentRankedSplit = $currentRankedSplits[0];
        return $this->findPlayerSeasonRank($player, $currentRankedSplit);
    }

    public function insert(PlayerSeasonRank $playerSeasonRank): void {
        $data = $this->mapEntityToData($playerSeasonRank);
        $columns = implode(",", array_keys($data));
        $placeholders = implode(",", array_fill(0, count($data), "?"));
        $values = array_values($data);

        $query = "INSERT INTO players_season_rank ($columns) VALUES ($placeholders)";
        $this->dbcn->execute_query($query, $values);

        $cacheKey = $playerSeasonRank->player->id."_".$playerSeasonRank->rankedSplit->season."_".$playerSeasonRank->rankedSplit->split;
        unset($this->cache[$cacheKey]);
    }

    public function update(PlayerSeasonRank $playerSeasonRank): RepositorySaveResult {
        $existingPlayerSeasonRank = $this->findPlayerSeasonRank($playerSeasonRank->player->id, $playerSeasonRank->rankedSplit, ignoreCache: true);
        $dataNew = $this->mapEntityToData($playerSeasonRank);
        $dataOld = $this->mapEntityToData($existingPlayerSeasonRank);
        $dataChanged = array_diff_assoc($dataNew, $dataOld);
        $dataPrevious = array_diff_assoc($dataOld, $dataNew);

        if (count($dataChanged) == 0) {
			return new RepositorySaveResult(SaveResult::NOT_CHANGED);
        }
        $set = implode(",", array_map(fn($key) => "$key = ?", array_keys($dataChanged)));
        $values = array_values($dataChanged);

        $query = "UPDATE players_season_rank SET $set WHERE OPL_ID_player = ? AND season = ? AND split = ?";
        $this->dbcn->execute_query($query, [...$values, $playerSeasonRank->player->id, $playerSeasonRank->rankedSplit->season, $playerSeasonRank->rankedSplit->split]);

        unset($this->cache[$playerSeasonRank->player->id."_".$playerSeasonRank->rankedSplit->season."_".$playerSeasonRank->rankedSplit->split]);
		return new RepositorySaveResult(SaveResult::UPDATED, $dataChanged, $dataPrevious);
    }

    public function save(PlayerSeasonRank $playerSeasonRank): RepositorySaveResult {
        try {
            if ($this->findPlayerSeasonRank($playerSeasonRank->player->id, $playerSeasonRank->rankedSplit)) {
                $saveResult = $this->update($playerSeasonRank);
            } else {
                $this->insert($playerSeasonRank);
				$saveResult = new RepositorySaveResult(SaveResult::INSERTED);
            }
        } catch (\Throwable $e) {
            $this->logger->error("Fehler beim Speichern von PlayerSeasonRank für {$playerSeasonRank->player->id} in {$playerSeasonRank->rankedSplit->getName()}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
			$saveResult = new RepositorySaveResult(SaveResult::FAILED);
        }
        $saveResult->entity = $this->findPlayerSeasonRank($playerSeasonRank->player->id, $playerSeasonRank->rankedSplit, ignoreCache: true);
        return $saveResult;
    }
}