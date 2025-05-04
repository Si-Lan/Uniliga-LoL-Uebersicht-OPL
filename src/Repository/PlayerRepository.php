<?php

namespace App\Repository;

use App\Database\DatabaseConnection;
use App\Entities\Player;

class PlayerRepository {
	private \mysqli $dbcn;

	public function __construct() {
		$this->dbcn = DatabaseConnection::getConnection();
	}

	public function findById(int $playerId): ?Player {
		$result = $this->dbcn->execute_query("SELECT * FROM players WHERE OPL_ID = ?", [$playerId]);
		$data = $result->fetch_assoc();

		$player = $data ? new Player(
			id: $data['OPL_ID'],
			name: $data['name'],
			riotIdName: $data['riotID_name'],
			riotIdTag: $data['riotID_tag'],
			summonerName: $data['summonerName'],
			summonerId: $data['summonerID'],
			puuid: $data['PUUID'],
			rankTier: $data['rank_tier'],
			rankDiv: $data['rank_div'],
			rankLp: $data['rank_LP'],
			matchesGotten: json_decode($data['matches_gotten']),
		) : null;

		return $player;
	}
}