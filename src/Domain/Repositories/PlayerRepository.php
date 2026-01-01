<?php

namespace App\Domain\Repositories;

use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Entities\Player;
use App\Domain\Entities\ValueObjects\RankForPlayer;
use App\Domain\Enums\SaveResult;

class PlayerRepository extends AbstractRepository {
	use DataParsingHelpers;

	protected static array $ALL_DATA_KEYS = ["OPL_ID","name","riotID_name","riotID_tag","summonerName","summonerID","PUUID","rank_tier","rank_div","rank_LP","matchesGotten"];
	protected static array $REQUIRED_DATA_KEYS = ["OPL_ID","name"];
	protected static array $OPL_DATA_KEYS = ["OPL_ID","name"];
	private array $cache = [];

	public function mapToEntity(array $data) : Player {
		$data = $this->normalizeData($data);
		return new Player(
			id: (int) $data['OPL_ID'],
			name: (string) $data['name'],
			riotIdName: $this->stringOrNull($data['riotID_name']),
			riotIdTag: $this->stringOrNull($data['riotID_tag']),
			summonerName: $this->stringOrNull($data['summonerName']),
			summonerId: $this->stringOrNull($data['summonerID']),
			puuid: $this->stringOrNull($data['PUUID']),
			rank: new RankForPlayer(
				$this->stringOrNull($data['rank_tier']),
				$this->stringOrNull($data['rank_div']),
				$this->intOrNull($data['rank_LP'])
			),
			matchesGotten: $this->decodeJsonOrDefault($data['matchesGotten'], "[]")
		);
	}

	public function findById(int $playerId, bool $ignoreCache = false): ?Player {
		if (isset($this->cache[$playerId]) && !$ignoreCache) {
			return $this->cache[$playerId];
		}
		$result = $this->dbcn->execute_query("SELECT * FROM players WHERE OPL_ID = ?", [$playerId]);
		$data = $result->fetch_assoc();

		$player = $data ? $this->mapToEntity($data) : null;
		if (!$ignoreCache) $this->cache[$playerId] = $player;

		return $player;
	}

	/**
	 * @return array<Player>
	 */
	public function findAll(): array {
		$result = $this->dbcn->execute_query("SELECT * FROM players");
		$data = $result->fetch_all(MYSQLI_ASSOC);
		$players = [];
		foreach ($data as $row) {
			$players[] = $this->mapToEntity($row);
		}
		return $players;
	}

	/**
	 * @param array<int> $playerIds
	 * @return array<Player>
	 */
	public function findAllByIds(array $playerIds): array {
		$placeholders = implode(",", array_fill(0, count($playerIds), "?"));
		$result = $this->dbcn->execute_query("SELECT * FROM players WHERE OPL_ID IN ($placeholders)", $playerIds);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$players = [];
		foreach ($data as $row) {
			$players[] = $this->mapToEntity($row);
		}
		return $players;
	}

	/**
	 * @param string $name
	 * @return array<Player>
	 */
	public function findAllByNameContains(string $name): array {
		$result = $this->dbcn->execute_query("SELECT * FROM players WHERE name LIKE ? OR riotID_name LIKE ?", ["%$name%","%$name%"]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$players = [];
		foreach ($data as $row) {
			$players[] = $this->mapToEntity($row);
		}
		return $players;
	}
	/**
	 * @param string $name
	 * @return array<Player>
	 */
	public function findAllByNameContainsLetters(string $name): array {
		$name = implode("%", str_split($name));
		$result = $this->dbcn->execute_query("SELECT * FROM players WHERE name LIKE ? OR riotID_name LIKE ?", ["%$name%","%$name%"]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$players = [];
		foreach ($data as $row) {
			$players[] = $this->mapToEntity($row);
		}
		return $players;
	}

	public function playerExists(int $playerId): bool {
		$result = $this->dbcn->execute_query("SELECT * FROM players WHERE OPL_ID = ?", [$playerId]);
		$data = $result->fetch_assoc();
		return $data !== null;
	}

	public function mapEntityToData(Player $player): array {
		return [
			"OPL_ID" => $player->id,
			"name" => $player->name,
			"riotID_name" => $player->riotIdName,
			"riotID_tag" => $player->riotIdTag,
			"summonerName" => $player->summonerName,
			"summonerID" => $player->summonerId,
			"PUUID" => $player->puuid,
			"rank_tier" => $player->rank?->rankTier,
			"rank_div" => $player->rank?->rankDiv,
			"rank_LP" => $player->rank?->rankLp,
			"matchesGotten" => json_encode($player->matchesGotten),
		];
	}

	public function insert(Player $player, bool $fromOplData = false): void {
		$data = $this->mapEntityToData($player);
		if ($fromOplData) {
			$data = $this->filterKeysFromOpl($data);
		}
		$columns = implode(",", array_keys($data));
		$placeholders = implode(",", array_fill(0, count($data), "?"));
		$values = array_values($data);

		/** @noinspection SqlInsertValues */
		$playerQuery = "INSERT INTO players ($columns) VALUES ($placeholders)";
		$this->dbcn->execute_query($playerQuery, $values);

		unset($this->cache[$player->id]);
	}

	/**
	 * @param Player $player
	 * @param bool $fromOplData
	 * @return array{'result': SaveResult, 'changes': array<string, mixed>}
	 */
	public function update(Player $player, bool $fromOplData = false): array {
		$existingPlayer = $this->findById($player->id, ignoreCache: true);
		$dataNew = $this->mapEntityToData($player);
		$dataOld = $this->mapEntityToData($existingPlayer);
		if ($fromOplData) {
			$dataNew = $this->filterKeysFromOpl($dataNew);
			$dataOld = $this->filterKeysFromOpl($dataOld);
		}

		$dataChanged = array_diff_assoc($dataNew, $dataOld);
		$dataPrevious = array_diff_assoc($dataOld, $dataNew);

		if (count($dataChanged) === 0) {
			return ['result'=>SaveResult::NOT_CHANGED];
		}

		$set = implode(",", array_map(fn($key) => "$key = ?", array_keys($dataChanged)));
		$values = array_values($dataChanged);

		$query = "UPDATE players SET $set WHERE OPL_ID = ?";
		$this->dbcn->execute_query($query, [...$values, $player->id]);

		unset($this->cache[$player->id]);
		return ['result'=>SaveResult::UPDATED, 'changes'=>$dataChanged, 'previous'=>$dataPrevious];
	}

	/**
	 * @param Player $player
	 * @param bool $fromOplData
	 * @return array{'result': SaveResult, 'changes': ?array<string, mixed>, 'player': ?Player}
	 */
	public function save(Player $player, bool $fromOplData = false): array {
		$saveResult = [];
		try {
			if ($this->playerExists($player->id)) {
				$saveResult = $this->update($player, $fromOplData);
			} else {
				$this->insert($player, $fromOplData);
				$saveResult = ['result'=>SaveResult::INSERTED];
			}
		} catch (\Throwable $e) {
			$this->logger->error("Fehler beim Speichern von Spieler $player->id: ".$e->getMessage() . "\n" . $e->getTraceAsString());
			$saveResult = ['result'=>SaveResult::FAILED];
		}
		$saveResult['player'] = $this->findById($player->id);
		return $saveResult;
	}

	public function createFromOplData(array $oplData): Player {
		$entityData = [
			"OPL_ID" => $oplData["ID"],
			"name" => $oplData["username"],
		];
		return $this->mapToEntity($entityData);
	}
}