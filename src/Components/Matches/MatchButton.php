<?php

namespace App\Components\Matches;

use App\Entities\Matchup;
use App\Entities\Team;
use App\Entities\TeamInTournament;
use App\Repositories\TeamInTournamentRepository;

class MatchButton {
	private ?TeamInTournament $team1InTournament = null;
	private ?TeamInTournament $team2InTournament = null;
	public function __construct(
		public Matchup $matchup,
		public ?Team $team = null,
		private ?TeamInTournamentRepository $teamInTournamentRepo = null
	) {
		if (is_null($this->teamInTournamentRepo)) {
			$this->teamInTournamentRepo = new TeamInTournamentRepository();
		}
		if ($this->matchup->team1 !== null) $this->team1InTournament = $this->teamInTournamentRepo->findByTeamAndTournament($this->matchup->team1,$this->matchup->tournamentStage->rootTournament);
		if ($this->matchup->team2 !== null) $this->team2InTournament = $this->teamInTournamentRepo->findByTeamAndTournament($this->matchup->team2,$this->matchup->tournamentStage->rootTournament);

	}

	public function render(): string {
		$matchup = $this->matchup;
		$currentTeam = $this->team;
		$team1InTournament = $this->team1InTournament;
		$team2InTournament = $this->team2InTournament;
		ob_start();
		include __DIR__.'/match-button.template.php';
		return ob_get_clean();
	}

	public function __toString(): string {
		return $this->render();
	}
}