<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\Game;
use App\Domain\Entities\GameInMatch;
use App\Domain\Entities\LolGame\GamePlayerData;
use App\Domain\Entities\Matchup;
use App\Domain\Entities\Team;
use App\Domain\Entities\TeamInTournament;
use App\Domain\Entities\TeamInTournamentStage;
use App\Domain\Enums\SaveResult;
use App\Domain\Factories\GameInMatchFactory;
use App\Domain\ValueObjects\RepositorySaveResult;

class GameInMatchRepository extends AbstractRepository {
	private GameInMatchFactory $factory;

	public function __construct() {
		parent::__construct();
		$this->factory = new GameInMatchFactory();
	}

	public function findByGameIdAndMatchupId(string $gameId, int $matchupId): ?GameInMatch {
		$query = 'SELECT * FROM games_to_matches WHERE RIOT_matchID = ? AND OPL_ID_matches = ?';
		$result = $this->dbcn->execute_query($query, [$gameId, $matchupId]);
		$data = $result->fetch_assoc();

		return $data ? $this->factory->createFromDbData($data) : null;
	}
	public function findByGameIdAndMatchup(string $gameId, Matchup $matchup): ?GameInMatch {
		$query = 'SELECT * FROM games_to_matches WHERE RIOT_matchID = ? AND OPL_ID_matches = ?';
		$result = $this->dbcn->execute_query($query, [$gameId, $matchup->id]);
		$data = $result->fetch_assoc();

		return $data ? $this->factory->createFromDbData($data, matchup: $matchup) : null;
	}
	public function findByGame(Game $game): ?GameInMatch {
		$query = 'SELECT * FROM games_to_matches WHERE RIOT_matchID = ?';
		$result = $this->dbcn->execute_query($query, [$game->id]);
		$data = $result->fetch_assoc();

		return $data ? $this->factory->createFromDbData($data, game: $game) : null;
	}

	/**
	 * @param Matchup $matchup
	 * @return array<GameInMatch>
	 */
	public function findAllByMatchup(Matchup $matchup): array {
		$query = 'SELECT * FROM games_to_matches WHERE OPL_ID_matches = ?';
		$result = $this->dbcn->execute_query($query, [$matchup->id]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$gamesInMatchup = [];
		foreach ($data as $gameData) {
			$gamesInMatchup[] = $this->factory->createFromDbData($gameData, matchup: $matchup);
		}
		return $gamesInMatchup;
	}

    /**
     * @param TeamInTournament $teamInTournament
     * @return array<GameInMatch>
     */
    public function findAllByTeamInTournament(TeamInTournament $teamInTournament): array {
        $query = 'SELECT gtm.*
                    FROM games_to_matches gtm
                    JOIN matchups m ON gtm.OPL_ID_matches = m.OPL_ID
                    JOIN tournaments t ON m.OPL_ID_tournament = t.OPL_ID
                    WHERE (gtm.OPL_ID_blueTeam = ? OR gtm.OPL_ID_redTeam = ?)
                      AND t.OPL_ID_top_parent = ?';
        $result = $this->dbcn->execute_query($query, [$teamInTournament->team->id, $teamInTournament->team->id, $teamInTournament->tournament->id]);
        $data = $result->fetch_all(MYSQLI_ASSOC);

        $gamesInMatchup = [];
        foreach ($data as $gameData) {
            $gamesInMatchup[] = $this->factory->createFromDbData($gameData);
        }
        return $gamesInMatchup;
    }

	public function gameIsInMatchup(string $gameId, int $matchupId): bool {
		$query = 'SELECT * FROM games_to_matches WHERE RIOT_matchID = ? AND OPL_ID_matches = ?';
		$result = $this->dbcn->execute_query($query, [$gameId, $matchupId]);
		return $result->num_rows > 0;
	}

	private function insert(GameInMatch $gameInMatch): void {
		$data = $this->factory->mapEntityToDBData($gameInMatch);
		$columns = implode(",", array_keys($data));
		$placeholders = implode(",", array_fill(0, count($data), "?"));
		$values = array_values($data);

		/** @noinspection SqlInsertValues */
		$query = "INSERT INTO games_to_matches ($columns) VALUES ($placeholders)";
		$this->dbcn->execute_query($query, $values);
	}

	private function update(GameInMatch $gameInMatch): RepositorySaveResult {
		$existingGameInMatch = $this->findByGameIdAndMatchupId($gameInMatch->game->id, $gameInMatch->matchup->id);

		$dataNew = $this->factory->mapEntityToDBData($gameInMatch);
		$dataOld = $this->factory->mapEntityToDBData($existingGameInMatch);
		$dataChanged = array_diff_assoc($dataNew, $dataOld);
		$dataPrevious = array_diff_assoc($dataOld, $dataNew);

		if (count($dataChanged) == 0) {
			return new RepositorySaveResult(SaveResult::NOT_CHANGED);
		}

		$set = implode(",", array_map(fn($key) => "$key = ?", array_keys($dataChanged)));
		$values = array_values($dataChanged);

		$query = "UPDATE games_to_matches SET $set WHERE RIOT_matchID = ? AND OPL_ID_matches = ?";
		$this->dbcn->execute_query($query, [...$values, $gameInMatch->game->id, $gameInMatch->matchup->id]);

		return new RepositorySaveResult(SaveResult::UPDATED, $dataChanged, $dataPrevious);
	}

	public function save(GameInMatch $gameInMatch): RepositorySaveResult {
		try {
			if ($this->gameIsInMatchup($gameInMatch->game->id, $gameInMatch->matchup->id)) {
				$saveResult = $this->update($gameInMatch);
			} else {
				$this->insert($gameInMatch);
				$saveResult = new RepositorySaveResult(SaveResult::INSERTED);
			}
		} catch (\Throwable $e) {
			$this->logger->error("Fehler beim Speichern von Spielen in Matchups: " . $e->getMessage() . "\n" . $e->getTraceAsString());
			$saveResult = new RepositorySaveResult(SaveResult::FAILED);
		}
		$saveResult->entity = $this->findByGameIdAndMatchupId($gameInMatch->game->id, $gameInMatch->matchup->id);
		return $saveResult;
	}

	public function delete(GameInMatch $gameInMatch): bool {
		if (!$this->gameIsInMatchup($gameInMatch->game->id, $gameInMatch->matchup->id)) {
			return false;
		}
		$query = "DELETE FROM games_to_matches WHERE RIOT_matchID = ? AND OPL_ID_matches = ?";
		return $this->dbcn->execute_query($query, [$gameInMatch->game->id, $gameInMatch->matchup->id]);
	}
}