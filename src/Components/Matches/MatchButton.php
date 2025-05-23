<?php

namespace App\Components\Matches;

use App\Entities\Matchup;
use App\Entities\Team;
use App\Repositories\TeamInTournamentRepository;

class MatchButton {
	public function __construct(
		public Matchup $matchup,
		public ?Team $team = null,
		private ?TeamInTournamentRepository $teamInTournamentRepo = null
	) {
		if (is_null($this->teamInTournamentRepo)) {
			$this->teamInTournamentRepo = new TeamInTournamentRepository();
		}
	}

	public function render(): string {
		$matchup = $this->matchup;
		$currentTeam = $this->team;
		ob_start();
		include __DIR__.'/match-button.template.php';
		return ob_get_clean();
	}

	public function __toString(): string {
		return $this->render();
	}
}