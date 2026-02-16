<?php

namespace App\UI\Components\Admin\Suggestions;

use App\Domain\Entities\MatchupChangeSuggestion;
use App\UI\Page\AssetManager;

class AdminSuggestionsPopupContent {
	/**
	 * @param array<MatchupChangeSuggestion> $suggestions
	 */
	public function __construct(
		private array $suggestions
	) {
		AssetManager::addCssAsset('admin/suggestions-popup.css');
		AssetManager::addCssAsset('components/matchPopup.css');
	}

	public function render(): string {
		$suggestions = $this->suggestions;
		ob_start();
		include __DIR__.'/admin-suggestions-popup-content.template.php';
		return ob_get_clean();
	}

	public function __toString(): string {
		return $this->render();
	}
}

