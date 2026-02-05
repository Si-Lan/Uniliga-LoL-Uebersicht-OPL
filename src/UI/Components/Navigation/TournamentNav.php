<?php

namespace App\UI\Components\Navigation;

use App\Domain\Entities\RankedSplit;
use App\Domain\Entities\Tournament;
use App\Domain\Repositories\RankedSplitRepository;
use App\UI\Page\AssetManager;

class TournamentNav {

	/**
	 * @param Tournament $tournament
	 * @param 'overview'|'teamlist'|'elo'|'' $activeTab
	 */
	public function __construct(
		private Tournament $tournament,
		private string $activeTab = ''
	) {
		AssetManager::addJsModule('components/tournamentNav');
	}

	public function render(): string {
		$tournament = $this->tournament;
		$activeTab = $this->activeTab;
		ob_start();
		include __DIR__.'/tournament-nav.template.php';
		return ob_get_clean();
	}
	public function __toString():string {
		return $this->render();
	}
}