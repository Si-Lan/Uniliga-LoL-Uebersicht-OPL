<?php

namespace App\UI\Components\Popups;

use App\Domain\Entities\TeamInTournament;

class TeamPopupContent {
	public function __construct(
		private TeamInTournament $teamInTournament
	) {}
	public function render(): string {
		$teamInTournament = $this->teamInTournament;
		ob_start();
		include __DIR__.'/team-popup-content.template.php';
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}
}