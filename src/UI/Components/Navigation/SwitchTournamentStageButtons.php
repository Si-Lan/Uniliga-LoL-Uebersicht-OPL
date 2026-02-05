<?php

namespace App\UI\Components\Navigation;

use App\Domain\Entities\TeamInTournamentStage;
use App\UI\Page\AssetManager;

class SwitchTournamentStageButtons {
	/**
	 * @param array<TeamInTournamentStage> $teamInTournamentStages
	 */
	public function __construct(
		private array $teamInTournamentStages,
		private TeamInTournamentStage $activeStage
	) {
		AssetManager::addJsModule('components/switchTeamEvents');
	}

	public function render(): string {
		$teamInTournamentStages = $this->teamInTournamentStages;
		$activeStage = $this->activeStage;
		ob_start();
		include __DIR__.'/switch-tournament-stage-buttons.template.php';
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}
}