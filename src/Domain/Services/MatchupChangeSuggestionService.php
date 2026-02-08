<?php

namespace App\Domain\Services;

use App\Domain\Entities\Matchup;
use App\Domain\Entities\MatchupChangeSuggestion;
use App\Domain\Repositories\GameInMatchRepository;
use App\Domain\Repositories\MatchupChangeSuggestionRepository;
use App\Domain\Repositories\MatchupRepository;

class MatchupChangeSuggestionService {
	private MatchupChangeSuggestionRepository $suggestionRepo;
	private MatchupRepository $matchupRepo;
	private GameInMatchRepository $gameInMatchRepo;
	public function __construct() {
		$this->suggestionRepo = new MatchupChangeSuggestionRepository();
		$this->matchupRepo = new MatchupRepository();
		$this->gameInMatchRepo = new GameInMatchRepository();
	}

	public function acceptSuggestion(MatchupChangeSuggestion $suggestion): void {
		$suggestion->accept();

		if ($suggestion->hasScoreChange()) {
			$suggestion->matchup->customTeam1Score = $suggestion->customTeam1Score !== null ? $suggestion->customTeam1Score : $suggestion->matchup->getTeam1Score();
			$suggestion->matchup->customTeam2Score = $suggestion->customTeam2Score !== null ? $suggestion->customTeam2Score : $suggestion->matchup->getTeam2Score();
			$suggestion->matchup->hasCustomScore = true;
		}

		if (count($suggestion->games) > 0) {

			$gamesToRemove = $this->gameInMatchRepo->findAllActiveByMatchup($suggestion->matchup);
			if (count($gamesToRemove) > 0) {
				foreach ($gamesToRemove as $gameToRemove) {
					if ($gameToRemove->oplConfirmed) {
						$gameToRemove->customAdded = false;
						$gameToRemove->customRemoved = true;
						$this->gameInMatchRepo->save($gameToRemove);
					} else {
						$this->gameInMatchRepo->delete($gameToRemove);
					}
				}
			}

			foreach ($suggestion->games as $game) {
				$game->customAdded = true;
				$game->customRemoved = false;

				$this->gameInMatchRepo->save($game);
			}
			$suggestion->matchup->hasCustomGames = true;
		}

		$this->suggestionRepo->save($suggestion);
		$this->matchupRepo->save($suggestion->matchup);
	}

	public function rejectSuggestion(MatchupChangeSuggestion $suggestion): void {
		$suggestion->reject();
		$this->suggestionRepo->save($suggestion);
	}

	public function revertSuggestionsForMatchup(Matchup $matchup): void {
		$matchup->hasCustomGames = false;
		$matchup->hasCustomScore = false;
		$this->matchupRepo->save($matchup);
		$games = $this->gameInMatchRepo->findAllByMatchup($matchup);
		foreach ($games as $game) {
			if ($game->oplConfirmed) {
				$game->customAdded = false;
				$game->customRemoved = false;
				$this->gameInMatchRepo->save($game);
			} else {
				$this->gameInMatchRepo->delete($game);
			}
		}
	}
}