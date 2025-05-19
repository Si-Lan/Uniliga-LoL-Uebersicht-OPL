<?php

namespace App\Components\Standings;

use App\Entities\Team;
use App\Entities\Tournament;
use App\Entities\TeamInTournamentStage;
use App\Repositories\TeamInTournamentStageRepository;

class StandingsTable {
	/** @var array<TeamInTournamentStage> */
	public array $teamsInTournamentStage;

	public function __construct(
		public Tournament $tournamentStage,
		public ?Team $selectedTeam = null
	) {
		$teamInTournamentStageRepo = new TeamInTournamentStageRepository();
		$this->teamsInTournamentStage = $teamInTournamentStageRepo->findAllByTournamentStage($this->tournamentStage, true);
	}

	public function render(): string {
		ob_start();
		include __DIR__.'/standings-table.template.php';
		return ob_get_clean();
	}

	public function __toString(): string {
		return $this->render();
	}
}