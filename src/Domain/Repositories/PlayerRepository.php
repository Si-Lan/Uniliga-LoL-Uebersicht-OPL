<?php

namespace App\Domain\Repositories;

use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Entities\Player;
use App\Domain\Entities\ValueObjects\RankForPlayer;

class PlayerRepository extends AbstractRepository {
	use DataParsingHelpers;

	protected static array $ALL_DATA_KEYS = ["OPL_ID","name","riotID_name","riotID_tag","summonerName","summonerID","PUUID","rank_tier","rank_div","rank_LP","matchesGotten"];
	protected static array $REQUIRED_DATA_KEYS = ["OPL_ID","name"];

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

	public function findById(int $playerId): ?Player {
		$result = $this->dbcn->execute_query("SELECT * FROM players WHERE OPL_ID = ?", [$playerId]);
		$data = $result->fetch_assoc();

		return $data ? $this->mapToEntity($data) : null;
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
}