<?php

namespace App\Components\Standings;

use App\Entities\TeamInTournamentStage;
use App\Entities\TeamSeasonRankInTournament;
use App\Repositories\TeamSeasonRankInTournamentRepository;

class StandingsRow {
	/** @var array<TeamSeasonRankInTournament> $teamSeasonRanksInTournament */
	public array $teamSeasonRanksInTournament;
	public function __construct(
		public TeamInTournamentStage $teamInTournamentStage,
		public ?int $previousRowStanding = 0,
		public bool $teamSelected = false
	) {}

	public function render(): string {
		ob_start();
		include __DIR__.'/standings-row.template.php';
		return ob_get_clean();
	}

	public function __toString(): string {
		return $this->render();
	}
}