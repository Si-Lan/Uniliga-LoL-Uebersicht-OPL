<?php

namespace App\Components\Cards;

use App\Entities\PlayerInTeamInTournament;
use App\Entities\TeamInTournament;
use App\Entities\TeamInTournamentStage;
use App\Entities\TeamSeasonRankInTournament;
use App\Enums\EventType;
use App\Repositories\PlayerInTeamInTournamentRepository;
use App\Repositories\TeamInTournamentStageRepository;
use App\Repositories\TeamSeasonRankInTournamentRepository;
use App\Utilities\EntitySorter;

class TeamInTournamentCard {
	private TeamInTournamentStage $TeamInTournamentStage;
	/**
	 * @var array<PlayerInTeamInTournament> $playersInTeamInTournament
	 */
	private array $playersInTeamInTournament;
	private ?TeamSeasonRankInTournament $teamSeasonRankInTournament;
	public function __construct(
		TeamInTournament $teamInTournament
	) {
		$teamInTournamentStageRepo = new TeamInTournamentStageRepository();
		$playerInTeamInTournamentRepo = new PlayerInTeamInTournamentRepository();
		$teamSeasonRankRepo = new TeamSeasonRankInTournamentRepository();

		$teamInTournamentStages = $teamInTournamentStageRepo->findAllbyTeamInTournament($teamInTournament);
		$teamInTournamentStages = EntitySorter::sortTeamInTournamentStages($teamInTournamentStages);
		foreach ($teamInTournamentStages as $index=>$teamInTournamentStage) {
			if ($teamInTournamentStage->tournamentStage->eventType == EventType::PLAYOFFS) {
				unset($teamInTournamentStages[$index]);
			}
		}
		$this->TeamInTournamentStage = end($teamInTournamentStages);

		$this->playersInTeamInTournament = $playerInTeamInTournamentRepo->findAllByTeamAndTournament($teamInTournament->team, $teamInTournament->tournament);
		$this->playersInTeamInTournament = EntitySorter::sortPlayersByMostPlayedRoles($this->playersInTeamInTournament);

		$this->teamSeasonRankInTournament = $teamSeasonRankRepo->findTeamSeasonRankInTournament($teamInTournament->team, $teamInTournament->tournament, $teamInTournament->tournament->rankedSplit);
	}

	public function render(): string {
		$teamInTournamentStage = $this->TeamInTournamentStage;
		$playersInTeamInTournament = $this->playersInTeamInTournament;
		$teamSeasonRankInTournament = $this->teamSeasonRankInTournament;
		ob_start();
		include __DIR__.'/team-in-tournament-card.template.php';
		return ob_get_clean();
	}

	public function __toString(): string {
		return $this->render();
	}
}