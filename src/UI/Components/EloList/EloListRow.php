<?php

namespace App\UI\Components\EloList;

use App\Domain\Entities\TeamInTournamentStage;
use App\Domain\Entities\TeamSeasonRankInTournament;
use App\Domain\Repositories\TeamSeasonRankInTournamentRepository;
use App\UI\Enums\EloListView;
use App\UI\Page\AssetManager;

class EloListRow {
	public function __construct(
		private TeamInTournamentStage $teamInTournamentStage,
		private TeamSeasonRankInTournament $teamSeasonRankInTournament,
		private EloListView $view
	) {
		AssetManager::addJsModule('components/pagePopups');
	}

	public function render(): string {
		$teamInTournamentStage = $this->teamInTournamentStage;
		$teamSeasonRankInTournament = $this->teamSeasonRankInTournament;
		$view = $this->view;
		ob_start();
		include __DIR__.'/elo-list-row.template.php';
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}
}