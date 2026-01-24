<?php

namespace App\UI\Components\Matches;

use App\Domain\Entities\Matchup;

class MatchRound {
	public function __construct(
		private Matchup $matchup
	) {}

	public function render(): string {
		$matchup = $this->matchup;
		ob_start();
		include __DIR__.'/match-round.template.php';
		return ob_get_clean();
	}

	public function __toString(): string {
		return $this->render();
	}
}