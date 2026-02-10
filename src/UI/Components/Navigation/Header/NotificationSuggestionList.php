<?php

namespace App\UI\Components\Navigation\Header;

use App\Domain\Entities\MatchupChangeSuggestion;

class NotificationSuggestionList {
	/**
	 * @param array<MatchupChangeSuggestion> $matchupChangeSuggestions
	 */
	public function __construct(
		private array $matchupChangeSuggestions
	) {}

	public function render(): string {
		$matchupChangeSuggestions = $this->matchupChangeSuggestions;
		ob_start();
		include __DIR__.'/notification-suggestion-list.template.php';
		return ob_get_clean();
	}

	public function __toString(): string {
		return $this->render();
	}
}