<?php

namespace App\Components\Navigation;

use App\Entities\TeamInTournamentStage;

class SwitchTournamentStageButtons {
	/**
	 * @param array<TeamInTournamentStage> $teamInTournamentStages
	 */
	public function __construct(
		private array $teamInTournamentStages,
		private TeamInTournamentStage $activeStage
	) {}

	public function render(): string {
		$teamInTournamentStages = $this->teamInTournamentStages;
		$activeStage = $this->activeStage;
		ob_start();
		include BASE_PATH.'/resources/components/navigation/switch-tournament-stage-buttons.php';
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}
}