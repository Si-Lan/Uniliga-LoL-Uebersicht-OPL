<?php

namespace App\Domain\Services;

use App\Domain\Entities\Game;
use App\Domain\Entities\LolGame\GamePlayerData;
use App\Domain\Entities\MatchupChangeSuggestions;
use App\Domain\Entities\TeamInTournament;
use App\Domain\Repositories\GameInMatchRepository;
use App\Domain\Repositories\MatchupChangeSuggestionRepository;
use App\Domain\Repositories\MatchupRepository;
use App\Domain\Repositories\PlayerInTeamInTournamentRepository;

class MatchupChangeSuggestionService {
	private MatchupChangeSuggestionRepository $suggestionRepo;
	private MatchupRepository $matchupRepo;
	private GameInMatchRepository $gameInMatchRepo;
	private PlayerInTeamInTournamentRepository $playerInTeamInTournamentRepo;
	public function __construct() {
		$this->suggestionRepo = new MatchupChangeSuggestionRepository();
		$this->matchupRepo = new MatchupRepository();
		$this->gameInMatchRepo = new GameInMatchRepository();
		$this->playerInTeamInTournamentRepo = new PlayerInTeamInTournamentRepository();
	}

	public function acceptSuggestion(MatchupChangeSuggestions $suggestion): void {
		$suggestion->accept();

		if ($suggestion->hasScoreChange()) {
			$suggestion->matchup->customTeam1Score = $suggestion->customTeam1Score !== null ? $suggestion->customTeam1Score : $suggestion->matchup->team1Score;
			$suggestion->matchup->customTeam2Score = $suggestion->customTeam2Score !== null ? $suggestion->customTeam2Score : $suggestion->matchup->team2Score;
			$suggestion->matchup->hasCustomScore = true;
		}

		if (count($suggestion->addedGames) > 0) {
			$suggestion->matchup->hasCustomGames = true;
			foreach ($suggestion->addedGames as $addedGame) {
				$gameInMatch = $this->gameInMatchRepo->findByGameIdAndMatchupId($addedGame->id, $suggestion->matchup->id);
				if ($gameInMatch === null) {
					$gameInMatch = $this->gameInMatchRepo->createFromEntities(
						$addedGame,
						$suggestion->matchup,
						null,
						null,
					);
				}

				$teamsToSide = $this->matchTeamsToSide($addedGame, $suggestion->matchup->team1, $suggestion->matchup->team2);

				$gameInMatch->blueTeam = $teamsToSide["blueTeam"];
				$gameInMatch->redTeam = $teamsToSide["redTeam"];
				$gameInMatch->customAdded = true;
				$gameInMatch->customRemoved = false;

				$this->gameInMatchRepo->save($gameInMatch);
			}
		}

		if (count($suggestion->removedGames) > 0) {
			$suggestion->matchup->hasCustomGames = true;
			foreach ($suggestion->removedGames as $removedGame) {
				$gameInMatch = $this->gameInMatchRepo->findByGameIdAndMatchupId($removedGame->id, $suggestion->matchup->id);
				if ($gameInMatch->oplConfirmed) {
					$gameInMatch->customAdded = false;
					$gameInMatch->customRemoved = true;
					$this->gameInMatchRepo->save($gameInMatch);
				} else {
					$this->gameInMatchRepo->delete($gameInMatch);
				}
			}
		}

		$this->suggestionRepo->save($suggestion);
		$this->matchupRepo->save($suggestion->matchup);
	}

	/**
	 * @param Game $game
	 * @param TeamInTournament $team1
	 * @param TeamInTournament $team2
	 * @return array{blueTeam: ?TeamInTournament, redTeam: ?TeamInTournament}
	 */
	private function matchTeamsToSide(Game $game, TeamInTournament $team1, TeamInTournament $team2): array {
		$bluePlayers = $game->gameData->blueTeamPlayers;
		$redPlayers = $game->gameData->redTeamPlayers;
		$bluePuuids = array_flip(array_map(fn(GamePlayerData $player) => $player->puuid, $bluePlayers));
		$redPuuids = array_flip(array_map(fn(GamePlayerData $player) => $player->puuid, $redPlayers));

		$team1Players = $this->playerInTeamInTournamentRepo->findAllByTeamInTournament($team1);
		$team2Players = $this->playerInTeamInTournamentRepo->findAllByTeamInTournament($team2);

		$team1Counter = ["blue" => 0, "red" => 0];
		foreach ($team1Players as $player) {
			if (isset($bluePuuids[$player->player->puuid])) {
				$team1Counter["blue"]++;
			}
			if (isset($redPuuids[$player->player->puuid])) {
				$team1Counter["red"]++;
			}
		}

		$team2Counter = ["blue" => 0, "red" => 0];
		foreach ($team2Players as $player) {
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

	public function rejectSuggestion(MatchupChangeSuggestions $suggestion): void {
		$suggestion->reject();
		$this->suggestionRepo->save($suggestion);
	}
}