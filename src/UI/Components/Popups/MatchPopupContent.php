<?php

namespace App\UI\Components\Popups;

use App\Domain\Entities\Matchup;
use App\Domain\Entities\Team;
use App\Domain\Repositories\GameInMatchRepository;
use App\UI\Page\AssetManager;

class MatchPopupContent {
	private array $gamesInMatch = [];
	public function __construct(
		private Matchup $matchup,
		private ?Team $team = null
	) {
		$gameInMatchRepo = new GameInMatchRepository();
		$gamesInMatch = $gameInMatchRepo->findAllByMatchup($matchup);
		$this->gamesInMatch = $gamesInMatch;
		AssetManager::addCssAsset('game.css');
	}
	public function render(): string {
		$matchup = $this->matchup;
		$team = $this->team;
		$gamesInMatch = $this->gamesInMatch;
		ob_start();
		include __DIR__.'/match-popup-content.template.php';
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}
}