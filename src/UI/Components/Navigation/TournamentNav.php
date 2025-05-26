<?php

namespace App\UI\Components\Navigation;

use App\Domain\Entities\RankedSplit;
use App\Domain\Entities\Tournament;
use App\Domain\Repositories\RankedSplitRepository;

class TournamentNav {
	private ?RankedSplit $nextSplit;
	/**
	 * @param Tournament $tournament
	 * @param 'overview'|'teamlist'|'elo'|'' $activeTab
	 */
	public function __construct(
		private Tournament $tournament,
		private string $activeTab = ''
	) {
		$rankedSplitRepo = new RankedSplitRepository();
		$this->nextSplit = $rankedSplitRepo->findNextSplitForTournament($tournament);
	}

	public function render(): string {
		$tournament = $this->tournament;
		$nextSplit = $this->nextSplit;
		$activeTab = $this->activeTab;
		ob_start();
		include __DIR__.'/tournament-nav.template.php';
		return ob_get_clean();
	}
	public function __toString():string {
		return $this->render();
	}
}