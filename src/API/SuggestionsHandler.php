<?php

namespace App\API;

use App\Domain\Enums\SuggestionStatus;
use App\Domain\Factories\GameInMatchFactory;
use App\Domain\Factories\MatchupChangeSuggestionFactory;
use App\Domain\Repositories\GameRepository;
use App\Domain\Repositories\MatchupChangeSuggestionRepository;
use App\Domain\Repositories\MatchupRepository;
use App\Service\Updater\GameUpdater;

class SuggestionsHandler extends AbstractHandler {
	private MatchupChangeSuggestionRepository $suggestionRepo;
	private MatchupChangeSuggestionFactory $suggestionFactory;
	private MatchupRepository $matchupRepo;
	private GameRepository $gameRepo;
	private GameUpdater $gameUpdater;
	private GameInMatchFactory $gameInMatchFactory;

	public function __construct() {
		$this->suggestionRepo = new MatchupChangeSuggestionRepository();
		$this->suggestionFactory = new MatchupChangeSuggestionFactory();
		$this->matchupRepo = new MatchupRepository();
		$this->gameRepo = new GameRepository();
		$this->gameUpdater = new GameUpdater();
		$this->gameInMatchFactory = new GameInMatchFactory();
	}

	public function postSuggestions(): void {
		$this->checkRequestMethod('POST');
		$data = $this->parseRequestData();
		$this->validateRequestData($data, ['matchupId', 'team1Score', 'team2Score','gameIds']);

		$matchup = $this->matchupRepo->findById($data['matchupId']);
		if ($matchup === null) {
			$this->sendErrorResponse(404, "Matchup not found");
		}

		$result = [
			"created" => false,
			"message" => ""
		];

		$pendingSuggestions = $this->suggestionRepo->findAllByMatchupIdAndStatus($matchup->id, SuggestionStatus::PENDING);

		if (count($pendingSuggestions) >= 3) {
			$result["message"] = "Für diesen Matchup gibt es bereits mehrere offene Vorschläge.";
			echo json_encode($result);
			exit();
		}

		$team1Score = $data['team1Score'] ?? null;
		$team2Score = $data['team2Score'] ?? null;

		if (!is_array($data['gameIds'])) {
			$this->sendErrorResponse(400, "gameIds must be an array");
		}
		$games = [];
		foreach ($data['gameIds'] as $gameId) {
			$game = $this->gameRepo->findById($gameId);
			if ($game === null) {
				$game = $this->gameRepo->createEmptyFromId($gameId);
				$this->gameRepo->save($game);
			}
			if ($game->gameData === null) {
				try {
					$this->gameUpdater->updateGameData($gameId);
				} catch (\Exception) {}
			}
			$gameInMatch = $this->gameInMatchFactory->createFromEntitiesAndImplyTeams($game, $matchup, customAdded: true);

			$games[] = $gameInMatch;
		}

		$suggestion = $this->suggestionFactory->createNew($matchup, $team1Score, $team2Score, $games);

		$repoSaveResult = $this->suggestionRepo->save($suggestion);

		if ($repoSaveResult->isSuccessful()) {
			$result["created"] = true;
			$result["message"] = "Vorschlag erfolgreich erstellt.";
		} elseif ($repoSaveResult->isFailed()) {
			$result["message"] = "Vorschlag konnte nicht erstellt werden.";
		}

		echo json_encode($result);
	}
}