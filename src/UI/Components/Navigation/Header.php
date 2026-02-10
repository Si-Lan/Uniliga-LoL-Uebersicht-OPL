<?php

namespace App\UI\Components\Navigation;

use App\Core\Utilities\UserContext;
use App\Domain\Entities\MatchupChangeSuggestion;
use App\Domain\Entities\Tournament;
use App\Domain\Enums\SuggestionStatus;
use App\Domain\Repositories\MatchupChangeSuggestionRepository;
use App\UI\Enums\HeaderType;
use App\UI\Page\AssetManager;

class Header {
	private MatchupChangeSuggestionRepository $matchupChangeSuggestionRepository;
	/** @var array<MatchupChangeSuggestion> */
	private array $matchupChangeSuggestions = [];
	public function __construct(
		private HeaderType $type = HeaderType::DEFAULT,
		private ?Tournament $tournament = null
	) {
		AssetManager::addJsModule('components/header');
		if (UserContext::isLoggedIn()) {
			$this->matchupChangeSuggestionRepository = new MatchupChangeSuggestionRepository();
			$this->matchupChangeSuggestions = $this->matchupChangeSuggestionRepository->findAllByStatus(SuggestionStatus::PENDING);
		}
	}

	public function render(): string {
		$type = $this->type;
		$tournament = $this->tournament;
		$matchupChangeSuggestions = $this->matchupChangeSuggestions;
		ob_start();
		include __DIR__.'/header.template.php';
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}
}