<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\Game;
use App\Domain\Entities\GameInMatch;
use App\Domain\Entities\Matchup;
use App\Domain\Entities\Team;
use App\Domain\Entities\TeamInTournament;
use App\Domain\Entities\TeamInTournamentStage;
use App\Domain\Enums\SaveResult;
use App\Domain\ValueObjects\RepositorySaveResult;

class GameInMatchRepository extends AbstractRepository {
	private GameRepository $gameRepo;
	private MatchupRepository $matchupRepo;
	private TeamInTournamentRepository $teamInTournamentRepo;
	protected static array $ALL_DATA_KEYS = [
		"RIOT_matchID",
		"OPL_ID_matches",
		"OPL_ID_blueTeam",
		"OPL_ID_redTeam",
		"opl_confirmed",
		"custom_added",
		"custom_removed"];
	protected static array $REQUIRED_DATA_KEYS = ["RIOT_matchID","OPL_ID_matches"];

	public function __construct() {
		parent::__construct();
		$this->gameRepo = new GameRepository();
		$this->matchupRepo = new MatchupRepository();
		$this->teamInTournamentRepo = new TeamInTournamentRepository();
	}

	public function mapToEntity(array $data, ?Game $game=null, ?Matchup $matchup=null, ?TeamInTournamentStage $blueTeam=null, ?TeamInTournamentStage $redTeam=null): GameInMatch {
		$data = $this->normalizeData($data);
		if (is_null($game)) {
			$game = $this->gameRepo->findById($data['RIOT_matchID']);
		}
		if (is_null($matchup)) {
			$matchup = $this->matchupRepo->findById($data['OPL_ID_matches']);
		}
		if (is_null($blueTeam) && !is_null($data['OPL_ID_blueTeam'])) {
			$blueTeam = $this->teamInTournamentRepo->findByTeamIdAndTournament($data['OPL_ID_blueTeam'],$matchup->tournamentStage->rootTournament);
		}
		if (is_null($redTeam) && !is_null($data['OPL_ID_redTeam'])) {
			$redTeam = $this->teamInTournamentRepo->findByTeamIdAndTournament($data['OPL_ID_redTeam'],$matchup->tournamentStage->rootTournament);
		}
		return new GameInMatch(
			game: $game,
			matchup: $matchup,
			blueTeam: $blueTeam,
			redTeam: $redTeam,
			oplConfirmed: (bool) $data['opl_confirmed']??false,
			customAdded: (bool) $data['custom_added']??false,
			customRemoved: (bool) $data['custom_removed']??false
		);
	}

	public function createFromEntities(
		Game $game,
		Matchup $matchup,
		TeamInTournamentStage|Team|null $blueTeam,
		TeamInTournamentStage|Team|null $redTeam,
		bool $oplConfirmed = false,
		bool $customAdded = false,
		bool $customRemoved = false
	): GameInMatch {
		$blueTeam = !($blueTeam instanceof Team) ? $blueTeam : $this->teamInTournamentRepo->findByTeamAndTournament($blueTeam, $matchup->tournamentStage->rootTournament);
		$redTeam = !($redTeam instanceof Team) ? $redTeam : $this->teamInTournamentRepo->findByTeamAndTournament($redTeam, $matchup->tournamentStage->rootTournament);
		return new GameInMatch(
			game: $game,
			matchup: $matchup,
			blueTeam: $blueTeam,
			redTeam: $redTeam,
			oplConfirmed: $oplConfirmed,
			customAdded: $customAdded,
			customRemoved: $customRemoved
		);
	}

	public function findByGameIdAndMatchupId(string $gameId, int $matchupId): ?GameInMatch {
		$query = 'SELECT * FROM games_to_matches WHERE RIOT_matchID = ? AND OPL_ID_matches = ?';
		$result = $this->dbcn->execute_query($query, [$gameId, $matchupId]);
		$data = $result->fetch_assoc();

		return $data ? $this->mapToEntity($data) : null;
	}
	public function findByGame(Game $game): ?GameInMatch {
		$query = 'SELECT * FROM games_to_matches WHERE RIOT_matchID = ?';
		$result = $this->dbcn->execute_query($query, [$game->id]);
		$data = $result->fetch_assoc();

		return $data ? $this->mapToEntity($data, game: $game) : null;
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
			$gamesInMatchup[] = $this->mapToEntity($gameData, matchup: $matchup);
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
            $gamesInMatchup[] = $this->mapToEntity($gameData);
        }
        return $gamesInMatchup;
    }

	public function gameIsInMatchup(string $gameId, int $matchupId): bool {
		$query = 'SELECT * FROM games_to_matches WHERE RIOT_matchID = ? AND OPL_ID_matches = ?';
		$result = $this->dbcn->execute_query($query, [$gameId, $matchupId]);
		return $result->num_rows > 0;
	}

	public function mapEntityToData(GameInMatch $gameInMatch): array {
		return [
			"RIOT_matchID" => $gameInMatch->game->id,
			"OPL_ID_matches" => $gameInMatch->matchup->id,
			"OPL_ID_blueTeam" => $gameInMatch->blueTeam?->team->id,
			"OPL_ID_redTeam" => $gameInMatch->redTeam?->team->id,
			"opl_confirmed" => (int) $gameInMatch->oplConfirmed,
			"custom_added" => (int) $gameInMatch->customAdded,
			"custom_removed" => (int) $gameInMatch->customRemoved
		];
	}

	private function insert(GameInMatch $gameInMatch): void {
		$data = $this->mapEntityToData($gameInMatch);
		$columns = implode(",", array_keys($data));
		$placeholders = implode(",", array_fill(0, count($data), "?"));
		$values = array_values($data);

		/** @noinspection SqlInsertValues */
		$query = "INSERT INTO games_to_matches ($columns) VALUES ($placeholders)";
		$this->dbcn->execute_query($query, $values);
	}

	private function update(GameInMatch $gameInMatch): RepositorySaveResult {
		$existingGameInMatch = $this->findByGameIdAndMatchupId($gameInMatch->game->id, $gameInMatch->matchup->id);

		$dataNew = $this->mapEntityToData($gameInMatch);
		$dataOld = $this->mapEntityToData($existingGameInMatch);
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