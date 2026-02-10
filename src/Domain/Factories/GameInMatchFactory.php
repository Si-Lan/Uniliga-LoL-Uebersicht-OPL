<?php

namespace App\Domain\Factories;

use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Entities\Game;
use App\Domain\Entities\GameInMatch;
use App\Domain\Entities\LolGame\GamePlayerData;
use App\Domain\Entities\Matchup;
use App\Domain\Entities\Team;
use App\Domain\Entities\TeamInTournament;
use App\Domain\Repositories\GameRepository;
use App\Domain\Repositories\MatchupRepository;
use App\Domain\Repositories\PlayerInTeamInTournamentRepository;
use App\Domain\Repositories\TeamInTournamentRepository;

class GameInMatchFactory extends AbstractFactory {
	use DataParsingHelpers;

	protected static array $DB_DATA_KEYS = [
		"RIOT_matchID",
		"OPL_ID_matches",
		"OPL_ID_blueTeam",
		"OPL_ID_redTeam",
		"opl_confirmed",
		"custom_added",
		"custom_removed"
	];
	protected static array $REQUIRED_DATA_KEYS = [
		"RIOT_matchID",
		"OPL_ID_matches"
	];

	public function __construct(
		private ?GameRepository $gameRepo = null,
		private ?MatchupRepository $matchupRepo = null,
		private ?TeamInTournamentRepository $teamInTournamentRepo = null,
		private ?PlayerInTeamInTournamentRepository $playerInTeamInTournamentRepo = null
	) {
		if ($this->gameRepo === null) $this->gameRepo = new GameRepository();
		if ($this->matchupRepo === null) $this->matchupRepo = new MatchupRepository();
		if ($this->teamInTournamentRepo === null) $this->teamInTournamentRepo = new TeamInTournamentRepository();
		if ($this->playerInTeamInTournamentRepo === null) $this->playerInTeamInTournamentRepo = new PlayerInTeamInTournamentRepository();
	}

	public function createFromDbData(
		array $data,
		?Game $game = null,
		?Matchup $matchup = null,
		?TeamInTournament $blueTeam = null,
		?TeamInTournament $redTeam = null
	): GameInMatch {
		$data = $this->normalizeDbData($data);

		if ($game === null) {
			$game = $this->gameRepo->findById($data['RIOT_matchID']);
		}
		if ($matchup === null) {
			$matchup = $this->matchupRepo->findById($data['OPL_ID_matches']);
		}
		if ($blueTeam === null && $data['OPL_ID_blueTeam'] !== null) {
			$blueTeam = $this->teamInTournamentRepo->findByTeamIdAndTournament($data['OPL_ID_blueTeam'],$matchup->tournamentStage->rootTournament);
		}
		if ($redTeam === null && $data['OPL_ID_redTeam'] !== null) {
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

	public function mapEntityToDbData(GameInMatch $gameInMatch): array {
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

	public function createFromEntities(
		Game $game,
		Matchup $matchup,
		TeamInTournament|Team|null $blueTeam,
		TeamInTournament|Team|null $redTeam,
		bool $oplConfirmed = false,
		bool $customAdded = false,
		bool $customRemoved = false
	): GameInMatch {
		$blueTeam = ($blueTeam instanceof TeamInTournament || $blueTeam === null) ? $blueTeam : $this->teamInTournamentRepo->findByTeamAndTournament($blueTeam, $matchup->tournamentStage->rootTournament);
		$redTeam = ($redTeam instanceof TeamInTournament || $redTeam === null) ? $redTeam : $this->teamInTournamentRepo->findByTeamAndTournament($redTeam, $matchup->tournamentStage->rootTournament);
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

	public function createFromEntitiesAndImplyTeams(
		Game $game,
		Matchup $matchup,
		bool $oplConfirmed = false,
		bool $customAdded = false,
		bool $customRemoved = false
	): GameInMatch
	{
		$teams = $this->matchTeamsToSide($game, $matchup->team1, $matchup->team2);
		return $this->createFromEntities(
			$game,
			$matchup,
			$teams["blueTeam"],
			$teams["redTeam"],
			$oplConfirmed,
			$customAdded,
			$customRemoved
		);
	}

	public function confirmTeamsForGameInMatch(GameInMatch $gameInMatch): GameInMatch {
		$teams = $this->matchTeamsToSide($gameInMatch->game, $gameInMatch->blueTeam, $gameInMatch->redTeam);
		$gameInMatch->blueTeam = $teams["blueTeam"];
		$gameInMatch->redTeam = $teams["redTeam"];
		return $gameInMatch;
	}

	/**
	 * @param Game $game
	 * @param TeamInTournament|null $team1
	 * @param TeamInTournament|null $team2
	 * @return array{blueTeam: ?TeamInTournament, redTeam: ?TeamInTournament}
	 */
	private function matchTeamsToSide(Game $game, ?TeamInTournament $team1, ?TeamInTournament $team2): array {
		if ($game->gameData === null) return ['blueTeam' => null, 'redTeam' => null];
		$bluePlayers = $game->gameData->blueTeamPlayers;
		$redPlayers = $game->gameData->redTeamPlayers;
		$bluePuuids = array_flip(array_map(fn(GamePlayerData $player) => $player->puuid, $bluePlayers));
		$redPuuids = array_flip(array_map(fn(GamePlayerData $player) => $player->puuid, $redPlayers));

		if ($team1 !== null) $team1Players = $this->playerInTeamInTournamentRepo->findAllByTeamInTournament($team1);
		if ($team2 !== null) $team2Players = $this->playerInTeamInTournamentRepo->findAllByTeamInTournament($team2);

		$team1Counter = ["blue" => 0, "red" => 0];
		foreach ($team1Players??[] as $player) {
			if (isset($bluePuuids[$player->player->puuid])) {
				$team1Counter["blue"]++;
			}
			if (isset($redPuuids[$player->player->puuid])) {
				$team1Counter["red"]++;
			}
		}

		$team2Counter = ["blue" => 0, "red" => 0];
		foreach ($team2Players??[] as $player) {
			if (isset($bluePuuids[$player->player->puuid])) {
				$team2Counter["blue"]++;
			}
			if (isset($redPuuids[$player->player->puuid])) {
				$team2Counter["red"]++;
			}
		}

		$result = ['blueTeam' => null, 'redTeam' => null];
		if ($team1Counter["blue"] > $team2Counter["blue"]) {
			$result["blueTeam"] = $team1;
		} elseif ($team1Counter["blue"] < $team2Counter["blue"]) {
			$result["blueTeam"] = $team2;
		}
		if ($team1Counter["red"] > $team2Counter["red"]) {
			$result["redTeam"] = $team1;
		} elseif ($team1Counter["red"] < $team2Counter["red"]) {
			$result["redTeam"] = $team2;
		}

		return $result;
	}
}