<?php

namespace App\Domain\Repositories;

use App\Core\Logger;
use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Entities\Game;
use App\Domain\Entities\Tournament;
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

    /**
     * @return array<Game>
     */
    public function findAllWithoutData(): array {
        $query = 'SELECT * FROM games WHERE matchdata IS NULL';
        $result = $this->dbcn->execute_query($query);
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $games = [];
        foreach ($data as $row) {
            $games[] = $this->mapToEntity($row);
        }
        return $games;
    }

    /**
     * @param Tournament $tournament
     * @return array<Game>
     */
    public function findAllWithoutDataByTournament(Tournament $tournament): array {
        $query = 'SELECT g.*
                    FROM games g
                        INNER JOIN games_to_matches gtm ON g.RIOT_matchID = gtm.RIOT_matchID
                        INNER JOIN matchups m ON gtm.OPL_ID_matches = m.OPL_ID
                        INNER JOIN tournaments t ON m.OPL_ID_tournament = t.OPL_ID
                    WHERE t.OPL_ID_top_parent = ?
                      AND g.matchdata IS NULL;';
        $result = $this->dbcn->execute_query($query, [$tournament->id]);
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $games = [];
        foreach ($data as $row) {
            $games[] = $this->mapToEntity($row);
        }
        return $games;
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
        $columns = implode(",", array_keys($data));
        $placeholders = implode(",", array_fill(0, count($data), "?"));
        $values = array_values($data);

		$query = "INSERT INTO games ($columns) VALUES ($placeholders)";
		$this->dbcn->execute_query($query, $values);
	}

    private function update(Game $game, bool $dontOverwriteGameData = false): array {
        $existingGame = $this->findById($game->id);

        $dataNew = $this->mapEntityToData($game);
        $dataOld = $this->mapEntityToData($existingGame);
        $dataChanges = array_diff_assoc($dataNew, $dataOld);
        $dataPrevious = array_diff_assoc($dataOld, $dataNew);

		if ($dontOverwriteGameData && array_key_exists('matchdata', $dataChanges)) {
			unset($dataChanges['matchdata']);
		}
        if (count($dataChanges) == 0) {
            return ['result' => SaveResult::NOT_CHANGED];
        }

        $set = implode(",", array_map(fn($key) => "$key = ?", array_keys($dataChanges)));
        $values = array_values($dataChanges);

        $query = "UPDATE games SET $set WHERE RIOT_matchID = ?";
        $this->dbcn->execute_query($query, [...$values, $game->id]);

        return ['result' => SaveResult::UPDATED, 'changes' => $dataChanges, 'previous' => $dataPrevious];
    }

	public function save(Game $game, bool $dontOverwriteGameData = false): array {
		try {
            if ($this->gameExists($game->id)) {
                $saveResult = $this->update($game, $dontOverwriteGameData);
            } else {
                $this->insert($game);
                $saveResult = ['result' => SaveResult::INSERTED];
            }
        } catch (\Throwable $e) {
            Logger::log('db', "Fehler beim Speichern der Spieldaten: " . $e->getMessage(). "\n" . $e->getTraceAsString());
            $saveResult['result'] = SaveResult::FAILED;
        }
		$saveResult['game'] = $this->findById($game->id);
		return $saveResult;
	}
}