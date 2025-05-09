<?php

namespace App\Components\Matches;

use App\Entities\Matchup;
use App\Entities\Team;
use App\Entities\TeamInTournament;
use App\Repositories\TeamInTournamentRepository;

class MatchButton {
	private TeamInTournament $team1InTournament;
	private TeamInTournament $team2InTournament;
	public function __construct(
		public Matchup $matchup,
		public ?Team $team=null
	) {
		$teamInTournamentRepo = new TeamInTournamentRepository();
		$this->team1InTournament = $teamInTournamentRepo->findByTeamAndTournament($this->matchup->team1,$this->matchup->tournamentStage->rootTournament);
		$this->team2InTournament = $teamInTournamentRepo->findByTeamAndTournament($this->matchup->team2,$this->matchup->tournamentStage->rootTournament);
	}

	public function render(): string {
		$matchup = $this->matchup;
		$currentTeam = $this->team;
		$team1InTournament = $this->team1InTournament;
		$team2InTournament = $this->team2InTournament;
		ob_start();
		include BASE_PATH.'/resources/components/matches/match-button.php';
		return ob_get_clean();
	}

	public function __toString(): string {
		return $this->render();
	}
}