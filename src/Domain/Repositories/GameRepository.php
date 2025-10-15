<?php

namespace App\Domain\Repositories;

use App\Core\Logger;
use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Entities\Game;
use App\Domain\Enums\SaveResult;

class GameRepository extends AbstractRepository {
	use DataParsingHelpers;

	protected static array $ALL_DATA_KEYS = ["RIOT_matchID","matchdata","played_at"];
	protected static array $REQUIRED_DATA_KEYS = ["RIOT_matchID"];
	protected static array $OPL_DATA_KEYS = ["RIOT_matchID"];

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

	public function gameExists(string $GameId): bool {
		$query = 'SELECT * FROM games WHERE RIOT_matchID = ?';
		$result = $this->dbcn->execute_query($query, [$GameId]);
		return $result->num_rows > 0;
	}

	public function mapEntityToData(Game $game): array {
		return [
			"RIOT_matchID" => $game->id,
			"matchdata" => json_encode($game->rawMatchdata),
			"played_at" => $game->playedAt?->format("Y-m-d H:i:s") ?? null,
		];
	}

	public function createEmptyFromId(string $gameId): Game {
		return new Game(
			id: $gameId,
			rawMatchdata: null,
			playedAt: null
		);
	}
	private function insert(Game $game): void {
		$data = $this->mapEntityToData($game);
		$query = 'INSERT INTO games (RIOT_matchID) VALUES (?)';
		$this->dbcn->execute_query($query, [$data['RIOT_matchID']]);
	}

	public function save(Game $game): array {
		$saveResult = [];
		if ($this->gameExists($game->id)) {
			$saveResult['result'] = SaveResult::NOT_CHANGED;
		} else {
			try {
				$this->insert($game);
				$saveResult['result'] = SaveResult::INSERTED;
			} catch (\Exception $e) {
				Logger::log('db', "Fehler beim Speichern der Spieldaten: " . $e->getMessage(). "\n" . $e->getTraceAsString());
				$saveResult['result'] = SaveResult::FAILED;
			}
		}
		$saveResult['game'] = $this->findById($game->id);
		return $saveResult;
	}
}