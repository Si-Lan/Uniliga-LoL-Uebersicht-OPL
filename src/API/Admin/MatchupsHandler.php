<?php

namespace App\API\Admin;

use App\API\AbstractHandler;
use App\Domain\Repositories\MatchupRepository;
use App\Domain\Services\MatchupChangeSuggestionService;

class MatchupsHandler extends AbstractHandler {
	private MatchupRepository $matchupRepo;
	private MatchupChangeSuggestionService $matchupChangeSuggestionService;
	public function __construct() {
		$this->matchupRepo = new MatchupRepository();
		$this->matchupChangeSuggestionService = new MatchupChangeSuggestionService();
	}

	public function postMatchupsSuggestionsRevert(int $matchupId): void {
		$this->checkRequestMethod('POST');
		$matchup = $this->matchupRepo->findById($matchupId);
		if ($matchup === null) {
			$this->sendErrorResponse(404, "Matchup not found");
		}

		try {
			$result = $this->matchupChangeSuggestionService->revertSuggestionsForMatchup($matchup);
		} catch (\Exception $e) {
			$this->sendErrorResponse($e->getCode(), $e->getMessage());
		}

		echo json_encode($result);
	}
}