<?php

namespace App\Components\Navigation;

use App\Entities\RankedSplit;
use App\Entities\Tournament;
use App\Repositories\RankedSplitRepository;

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
		include BASE_PATH.'/resources/components/navigation/tournament-nav.php';
		return ob_get_clean();
	}
	public function __toString():string {
		return $this->render();
	}
}