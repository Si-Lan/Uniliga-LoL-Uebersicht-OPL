<?php

namespace App\UI\Components\Matches;

use App\Domain\Entities\Matchup;
use App\Domain\Entities\Team;
use App\Domain\Repositories\TeamInTournamentRepository;
use App\UI\Page\AssetManager;

class MatchButton {
	private $popupOpened = false;
	public function __construct(
		public Matchup $matchup,
		public ?Team $team = null,
		private ?TeamInTournamentRepository $teamInTournamentRepo = null,
		public ?string $injectedPopupId = null
	) {
		if (is_null($this->teamInTournamentRepo)) {
			$this->teamInTournamentRepo = new TeamInTournamentRepository();
		}
		$this->popupOpened = isset($_GET['match']) && $_GET['match'] == $matchup->id;
		AssetManager::addJsAsset('components/pagePopups.js');
	}

	public function render(): string {
		$matchup = $this->matchup;
		$currentTeam = $this->team;
		$popupOpened = $this->popupOpened;
		$injectedPopupId = $this->injectedPopupId;
		ob_start();
		include __DIR__.'/match-button.template.php';
		return ob_get_clean();
	}

	public function __toString(): string {
		return $this->render();
	}
}