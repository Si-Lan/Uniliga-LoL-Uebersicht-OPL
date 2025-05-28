<?php

namespace App\UI\Components\EloList;

use App\Domain\Entities\TeamInTournamentStage;
use App\Domain\Entities\Tournament;
use App\Domain\Enums\EventType;
use App\Domain\Repositories\TeamInTournamentStageRepository;
use App\Domain\Repositories\TeamSeasonRankInTournamentRepository;
use App\Domain\Repositories\TournamentRepository;
use App\Domain\Services\EntitySorter;
use App\UI\Enums\EloListView;

class EloList {
	private array $teamsInTournamentStages = [];
	private array $teamSeasonRankMap;
	public function __construct(
		private Tournament $tournament,
		private EloListView $view,
		private ?TeamSeasonRankInTournamentRepository $teamSeasonRankInTournamentRepo = null
	) {
		$tournamentRepo = new TournamentRepository();
		$teamInTournamentStageRepo = new TeamInTournamentStageRepository();
		if (is_null($this->teamSeasonRankInTournamentRepo)) {
			$teamSeasonRankInTournamentRepo = new TeamSeasonRankInTournamentRepository();
		}

		if ($this->tournament->eventType === EventType::TOURNAMENT && $this->view === EloListView::ALL) {
			$this->teamsInTournamentStages = $teamInTournamentStageRepo->findAllInGroupStageByRootTournament($this->tournament);
		} elseif ($this->tournament->eventType === EventType::TOURNAMENT && $view === EloListView::WILDCARD_ALL) {
			$this->teamsInTournamentStages = $teamInTournamentStageRepo->findAllWildcardsByRootTournament($this->tournament);
		} elseif ($this->tournament->isEventWithStanding()) {
			$this->teamsInTournamentStages = $teamInTournamentStageRepo->findAllByTournamentStage($this->tournament, false);
		} elseif ($this->tournament->eventType === EventType::LEAGUE) {
			$tournamentStages = $tournamentRepo->findAllByParentTournamentAndType($this->tournament, EventType::GROUP);
			$this->teamsInTournamentStages = [];
			foreach ($tournamentStages as $tournamentStage) {
				$this->teamsInTournamentStages = array_merge($this->teamsInTournamentStages, $teamInTournamentStageRepo->findAllByTournamentStage($tournamentStage, false));
			}
		}

		$this->teamsInTournamentStages = EntitySorter::removeDuplicateTeamsInTournamentStages($this->teamsInTournamentStages);

		$this->teamSeasonRankMap = $teamSeasonRankInTournamentRepo->getRankMapForTournamentStage($this->tournament);

		usort($this->teamsInTournamentStages, function(TeamInTournamentStage $a, TeamInTournamentStage $b) {
			$splitKey = $a->teamInRootTournament->tournament->userSelectedRankedSplit->getName();
			$aRank = $this->teamSeasonRankMap[$a->team->id][$splitKey];
			$bRank = $this->teamSeasonRankMap[$b->team->id][$splitKey];
			if ($aRank->rank->getRankNum() == $bRank->rank->getRankNum()) {
				$aComparer = ($a->tournamentStage->eventType == EventType::GROUP) ? $a->tournamentStage->getDirectParentTournament()->number : $a->tournamentStage->number;
				$bComparer = ($b->tournamentStage->eventType == EventType::GROUP) ? $b->tournamentStage->getDirectParentTournament()->number : $b->tournamentStage->number;
				if ($aComparer == $bComparer) {
					return $a->team->name <=> $b->team->name;
				}
				return $aComparer <=> $bComparer;
			}
			return $bRank->rank->getRankNum() <=> $aRank->rank->getRankNum();
		});
	}

	public function render(): string {
		$view = $this->view;
		$tournament = $this->tournament;
		$teamsInTournamentStages = $this->teamsInTournamentStages;
		$teamSeasonRankMap = $this->teamSeasonRankMap;
		ob_start();
		include __DIR__.'/elo-list.template.php';
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}
}