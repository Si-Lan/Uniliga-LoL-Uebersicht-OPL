<?php

namespace App\API\Admin;

use App\API\AbstractHandler;
use App\Domain\Factories\MatchupChangeSuggestionFactory;
use App\Domain\Repositories\MatchupChangeSuggestionRepository;
use App\Domain\Services\MatchupChangeSuggestionService;

class SuggestionsHandler extends AbstractHandler {
	private MatchupChangeSuggestionRepository $suggestionRepo;
	private MatchupChangeSuggestionService $suggestionService;

	public function __construct() {
		$this->suggestionRepo = new MatchupChangeSuggestionRepository();
		$this->suggestionService = new MatchupChangeSuggestionService();
	}

	public function postSuggestionsAccept(int $suggestionId): void {
		$this->checkRequestMethod('POST');
		$suggestion = $this->suggestionRepo->findById($suggestionId);
		if ($suggestion === null) {
			$this->sendErrorResponse(404, "Suggestion not found");
		}

		try {
			$result = $this->suggestionService->acceptSuggestion($suggestion);
		} catch (\Exception $e) {
			$this->sendErrorResponse($e->getCode(), $e->getMessage());
		}
		echo json_encode($result);
	}

	public function postSuggestionsReject(int $suggestionId): void {
		$this->checkRequestMethod('POST');
		$suggestion = $this->suggestionRepo->findById($suggestionId);
		if ($suggestion === null) {
			$this->sendErrorResponse(404, "Suggestion not found");
		}

		try {
			$result = $this->suggestionService->rejectSuggestion($suggestion);
		} catch (\Exception $e) {
			$this->sendErrorResponse($e->getCode(), $e->getMessage());
		}
		echo json_encode($result);
	}
}