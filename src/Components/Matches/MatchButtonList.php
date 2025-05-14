<?php

namespace App\Components\Matches;

use App\Entities\Matchup;
use App\Entities\Team;
use App\Entities\Tournament;
use App\Repositories\MatchupRepository;
use App\Repositories\TeamInTournamentRepository;
use App\Utilities\EntitySorter;

class MatchButtonList {
	/** @var array<Matchup> $matchups  */
	private array $matchupRounds;
	private TeamInTournamentRepository $teamInTournamentRepository; // Für die Matchbuttons
	public function __construct(
		public Tournament $tournamentStage,
		public ?Team $team=null
	) {
		$matchupRepo = new MatchupRepository();
		if ($team != null) {
			$this->matchupRounds = $matchupRepo->findAllByTournamentStageAndTeam($this->tournamentStage, $this->team);
		} else {
			$this->matchupRounds = $matchupRepo->findAllWithATeamByTournamentStage($this->tournamentStage);
		}
		if ($this->tournamentStage->isEventWithRounds()) {
			$this->matchupRounds = EntitySorter::sortAndGroupMatchupsByPlayday($this->matchupRounds);
		} else {
			$this->matchupRounds = EntitySorter::sortAndGroupMatchupsByPlannedDate($this->matchupRounds);
		}
		$this->teamInTournamentRepository = new TeamInTournamentRepository();
	}

	public function render(): string {
		$matchupRounds = $this->matchupRounds;
		$tournamentStage = $this->tournamentStage;
		$team = $this->team;
		$teamInTournamentRepository = $this->teamInTournamentRepository;
		ob_start();
		include BASE_PATH.'/resources/components/matches/match-button-list.php';
		return ob_get_clean();
	}

	public function __toString(): string {
		return $this->render();
	}
}