<?php

namespace App\Components\Navigation;

use App\Entities\TeamInTournament;

class TeamHeaderNav {
	public function __construct(
		private TeamInTournament $teamInTournament,
		private string $activeTab = ''
	) {}

	public function render(): string {
		$teamInTournament = $this->teamInTournament;
		$activeTab = $this->activeTab;
		ob_start();
		include __DIR__.'/team-header-nav.template.php';
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}
}