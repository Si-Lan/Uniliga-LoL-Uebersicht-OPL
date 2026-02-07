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
	public function __construct() {
		$this->suggestionRepo = new MatchupChangeSuggestionRepository();
		$this->matchupRepo = new MatchupRepository();
		$this->gameInMatchRepo = new GameInMatchRepository();
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
				$addedGame->customAdded = true;
				$addedGame->customRemoved = false;

				$this->gameInMatchRepo->save($addedGame);
			}
		}

		if (count($suggestion->removedGames) > 0) {
			$suggestion->matchup->hasCustomGames = true;
			foreach ($suggestion->removedGames as $removedGame) {
				if ($removedGame->oplConfirmed) {
					$removedGame->customAdded = false;
					$removedGame->customRemoved = true;
					$this->gameInMatchRepo->save($removedGame);
				} else {
					$this->gameInMatchRepo->delete($removedGame);
				}
			}
		}

		$this->suggestionRepo->save($suggestion);
		$this->matchupRepo->save($suggestion->matchup);
	}

	public function rejectSuggestion(MatchupChangeSuggestions $suggestion): void {
		$suggestion->reject();
		$this->suggestionRepo->save($suggestion);
	}
}