<?php

namespace App\Components\Player;

use App\Entities\Patch;
use App\Entities\PlayerInTeamInTournament;
use App\Entities\PlayerSeasonRank;
use App\Enums\EventType;
use App\Repositories\PatchRepository;
use App\Repositories\PlayerSeasonRankRepository;
use App\Repositories\RankedSplitRepository;
use App\Repositories\TeamInTournamentStageRepository;
use App\Utilities\EntitySorter;

class PlayerInTournamentInTeamCard {
	private TeamInTournamentStageRepository $teamInTournamentStageRepo;
	private ?PlayerSeasonRank $playerSeasonRank;
	private Patch $latestPatch;
	public function __construct(
		private PlayerInTeamInTournament $playerInTeamInTournament
	) {
		$this->teamInTournamentStageRepo = new TeamInTournamentStageRepository();
		$rankedSplitRepo = new RankedSplitRepository();
		$rankedSplit = $rankedSplitRepo->findFirstSplitForTournament($playerInTeamInTournament->teamInTournament->tournament);
		$playerSeasonRankRepo = new PlayerSeasonRankRepository();
		$this->playerSeasonRank = $playerSeasonRankRepo->findPlayerSeasonRank($playerInTeamInTournament->player, $rankedSplit);

		$patchRepo = new PatchRepository();
		$this->latestPatch = $patchRepo->findLatestPatchWithAllData();
	}

	public function render(): string {
		$playerInTeamInTournament = $this->playerInTeamInTournament;
		$teamInTournamentStages = $this->teamInTournamentStageRepo->findAllbyTeamInTournament($playerInTeamInTournament->teamInTournament);
		$teamInTournamentStages = EntitySorter::sortTeamInTournamentStages($teamInTournamentStages);
		if (end($teamInTournamentStages)->tournamentStage->eventType === EventType::PLAYOFFS && count($teamInTournamentStages) > 1) {
			$teamInTournamentStage = $teamInTournamentStages[0];
			foreach ($teamInTournamentStages as $teamInTournamentStageInLoop) {
				if ($teamInTournamentStageInLoop->tournamentStage->eventType === EventType::PLAYOFFS) continue;
				$teamInTournamentStage = $teamInTournamentStageInLoop;
			}
			$teamInPlayoffs = end($teamInTournamentStages);
		} else {
			$teamInTournamentStage = end($teamInTournamentStages);
			$teamInPlayoffs = null;
		}
		$playerSeasonRank = $this->playerSeasonRank;
		$latestPatch = $this->latestPatch;

		ob_start();
		include __DIR__ . '/player-in-tournament-in-team-card.template.php';
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}
}