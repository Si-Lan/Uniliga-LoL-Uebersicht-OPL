<?php

namespace App\UI\Components\Admin\Suggestions;

use App\Domain\Entities\Matchup;
use App\Domain\Entities\MatchupChangeSuggestion;

class AdminSuggestionDetails {
	/**
	 * @param Matchup $matchup
	 * @param array<MatchupChangeSuggestion> $suggestions
	 */
	public function __construct(
		private Matchup $matchup,
		private ?array $suggestions = null
	) {}

	public function render(): string {
		$matchup = $this->matchup;
		$suggestions = $this->suggestions;
		ob_start();
		include __DIR__.'/admin-suggestion-details.template.php';
		return ob_get_clean();
	}

	public function __toString(): string {
		return $this->render();
	}
}

