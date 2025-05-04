<?php

namespace App\Repositories;

use App\Database\DatabaseConnection;
use App\Entities\Player;
use App\Utilities\NullableCastTrait;

class PlayerRepository {
	use NullableCastTrait;

	private \mysqli $dbcn;

	public function __construct() {
		$this->dbcn = DatabaseConnection::getConnection();
	}

	public function createEntityFromData(array $data) : Player {
		return new Player(
			id: (int) $data['OPL_ID'],
			name: (string) $data['name'],
			riotIdName: $this->nullableString($data['riotID_name']??null),
			riotIdTag: $this->nullableString($data['riotID_tag']??null),
			summonerName: $this->nullableString($data['summonerName']??null),
			summonerId: $this->nullableString($data['summonerID']??null),
			puuid: $this->nullableString($data['PUUID']??null),
			rankTier: $this->nullableString($data['rank_tier']??null),
			rankDiv: $this->nullableString($data['rank_div']??null),
			rankLp: $this->nullableInt($data['rank_LP']??null),
			matchesGotten: json_validate($data['matches_gotten']??"[]") ? json_decode($data['matches_gotten']) : [],
		);
	}

	public function findById(int $playerId): ?Player {
		$result = $this->dbcn->execute_query("SELECT * FROM players WHERE OPL_ID = ?", [$playerId]);
		$data = $result->fetch_assoc();

		$player = $data ? $this->createEntityFromData($data) : null;

		return $player;
	}
}