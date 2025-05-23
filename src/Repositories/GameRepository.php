<?php

namespace App\Repositories;

use App\Entities\Game;
use App\Utilities\DataParsingHelpers;

class GameRepository extends AbstractRepository {
	use DataParsingHelpers;

	protected static array $ALL_DATA_KEYS = ["RIOT_matchID","matchdata","played_at"];
	protected static array $REQUIRED_DATA_KEYS = ["RIOT_matchID"];

	public function mapToEntity(array $data): Game {
		$data = $this->normalizeData($data);
		return new Game(
			id: (string) $data['RIOT_matchID'],
			rawMatchdata: $this->decodeJsonOrNull($data['matchdata']),
			playedAt: $this->DateTimeImmutableOrNull($data['played_at'])
		);
	}

	public function findById(string $GameId): ?Game {
		$query = 'SELECT * FROM games WHERE RIOT_matchID = ?';
		$result = $this->dbcn->execute_query($query, [$GameId]);
		$data = $result->fetch_assoc();

		return $data ? $this->mapToEntity($data) : null;
	}
}