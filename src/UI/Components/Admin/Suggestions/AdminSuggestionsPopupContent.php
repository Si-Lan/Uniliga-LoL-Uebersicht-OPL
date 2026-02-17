<?php

namespace App\UI\Components\Admin\Suggestions;

use App\Domain\Entities\Matchup;
use App\Domain\Entities\MatchupChangeSuggestion;
use App\UI\Page\AssetManager;

class AdminSuggestionsPopupContent {
	/**
	 * @param array<MatchupChangeSuggestion> $suggestions
	 * @param array<Matchup> $changedMatchups
	 */
	public function __construct(
		private array $suggestions,
		private array $changedMatchups = [],
		private string $openTab = 'suggestions'
	) {
		AssetManager::addCssAsset('admin/suggestions-popup.css');
		AssetManager::addCssAsset('components/matchPopup.css');
	}

	public function render(): string {
		$suggestions = $this->suggestions;
		$changedMatchups = $this->changedMatchups;
		$openTab = $this->openTab;
		ob_start();
		include __DIR__.'/admin-suggestions-popup-content.template.php';
		return ob_get_clean();
	}

	public function __toString(): string {
		return $this->render();
	}
}

