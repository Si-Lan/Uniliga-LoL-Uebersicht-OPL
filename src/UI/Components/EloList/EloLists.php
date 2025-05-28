<?php

namespace App\UI\Components\EloList;

use App\Domain\Entities\Tournament;
use App\Domain\Enums\EventType;
use App\Domain\Repositories\TeamSeasonRankInTournamentRepository;
use App\Domain\Repositories\TournamentRepository;
use App\UI\Enums\EloListView;

class EloLists {
	private TournamentRepository $tournamentRepo;
	private TeamSeasonRankInTournamentRepository $teamSeasonRankInTournamentRepo;
	public function __construct(
		private Tournament $tournament,
		private EloListView $view
	) {
		$this->tournamentRepo = new TournamentRepository();
		$this->teamSeasonRankInTournamentRepo = new TeamSeasonRankInTournamentRepository();
	}

	public function render(): string {
		ob_start();
		switch ($this->view) {
			case EloListView::BY_LEAGUES:
				$leagues = $this->tournamentRepo->findAllByRootTournamentAndType($this->tournament, EventType::LEAGUE);
				$this->renderEloLists($leagues);
				break;

			case EloListView::BY_GROUPS:
				$leagues = $this->tournamentRepo->findAllByParentTournamentAndType($this->tournament, EventType::LEAGUE);
				foreach ($leagues as $league) {
					if ($league->isEventWithStanding()) {
						echo new EloList($league, $this->view, $this->teamSeasonRankInTournamentRepo);
						continue;
					}
					$groups = $this->tournamentRepo->findAllByParentTournamentAndType($league, EventType::GROUP);
					$this->renderEloLists($groups);
				}
				break;

			case EloListView::WILDCARD_BY_LEAGUES:
				$wildcards = $this->tournamentRepo->findAllByRootTournamentAndType($this->tournament, EventType::WILDCARD);
				$this->renderEloLists($wildcards);
				break;

			case EloListView::WILDCARD_ALL:
			case EloListView::ALL:
			default:
				echo new EloList($this->tournament, $this->view, $this->teamSeasonRankInTournamentRepo);
		}

		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}

	private function renderEloLists(array $tournamentStages): void {
		foreach ($tournamentStages as $tournamentStage) {
			echo new EloList($tournamentStage, $this->view, $this->teamSeasonRankInTournamentRepo);
		}
	}
}