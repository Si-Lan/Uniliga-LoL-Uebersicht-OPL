<?php

namespace App\Repositories;

use App\Database\DatabaseConnection;
use App\Entities\Player;
use App\Utilities\DataParsingHelpers;

class PlayerRepository {
	use DataParsingHelpers;

	private \mysqli $dbcn;

	public function __construct() {
		$this->dbcn = DatabaseConnection::getConnection();
	}

	public function mapToEntity(array $data) : Player {
		return new Player(
			id: (int) $data['OPL_ID'],
			name: (string) $data['name'],
			riotIdName: $this->stringOrNull($data['riotID_name']??null),
			riotIdTag: $this->stringOrNull($data['riotID_tag']??null),
			summonerName: $this->stringOrNull($data['summonerName']??null),
			summonerId: $this->stringOrNull($data['summonerID']??null),
			puuid: $this->stringOrNull($data['PUUID']??null),
			rankTier: $this->stringOrNull($data['rank_tier']??null),
			rankDiv: $this->stringOrNull($data['rank_div']??null),
			rankLp: $this->intOrNull($data['rank_LP']??null),
			matchesGotten: $this->decodeJsonOrDefault($data['matchesGotten']??null, "[]")
		);
	}

	public function findById(int $playerId): ?Player {
		$result = $this->dbcn->execute_query("SELECT * FROM players WHERE OPL_ID = ?", [$playerId]);
		$data = $result->fetch_assoc();

		$player = $data ? $this->mapToEntity($data) : null;

		return $player;
	}
}