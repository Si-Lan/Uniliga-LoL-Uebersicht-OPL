<?php

namespace App\Repositories;

use App\Entities\Player;
use App\Entities\ValueObjects\RankForPlayer;
use App\Utilities\DataParsingHelpers;

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

	public function playerExists(int $playerId): bool {
		$result = $this->dbcn->execute_query("SELECT * FROM players WHERE OPL_ID = ?", [$playerId]);
		return $result !== false;
	}
}