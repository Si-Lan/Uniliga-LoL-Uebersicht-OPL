<?php

namespace App\Repositories;

use App\Entities\Game;
use App\Entities\Matchup;
use App\Entities\GameInMatch;
use App\Entities\TeamInTournamentStage;

class GameInMatchRepository extends AbstractRepository {
	private GameRepository $gameRepo;
	private MatchupRepository $matchupRepo;
	private TeamInTournamentRepository $teamInTournamentRepo;
	protected static array $ALL_DATA_KEYS = ["RIOT_matchID","OPL_ID_matches","OPL_ID_blueTeam","OPL_ID_redTeam","opl_confirmed"];
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
		if (is_null($blueTeam)) {
			$blueTeam = $this->teamInTournamentRepo->findByTeamIdAndTournament($data['OPL_ID_blueTeam'],$matchup->tournamentStage->rootTournament);
		}
		if (is_null($redTeam)) {
			$redTeam = $this->teamInTournamentRepo->findByTeamIdAndTournament($data['OPL_ID_redTeam'],$matchup->tournamentStage->rootTournament);
		}
		return new GameInMatch(
			game: $game,
			matchup: $matchup,
			blueTeam: $blueTeam,
			redTeam: $redTeam,
			oplConfirmed: (bool) $data['opl_confirmed']??false
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
}