<?php

namespace App\UI\Components\Matches\ChangeSuggestions;

use App\Domain\Entities\GameInMatch;
use App\Domain\Entities\Patch;
use App\Domain\Factories\GameInMatchFactory;
use App\Domain\Repositories\PatchRepository;

class GameSuggestionDetails {
	private Patch $patch;
	private GameInMatchFactory $gameInMatchFactory;
	public function __construct(
		private ?GameInMatch $gameInMatch = null,
		private bool $selectable = true,
		private ?string $errorMessage = null,
	) {
		$patchRepo = new PatchRepository();
		if ($this->gameInMatch === null) {
			if ($this->errorMessage === null) $this->errorMessage = "Keine Spieldaten vorhanden";
			return;
		};
		if ($this->gameInMatch->game?->gameData === null) {
			$this->errorMessage = "Keine Spieldaten gefunden";
		} else {
			$this->gameInMatchFactory = new GameInMatchFactory();
			$this->gameInMatch = $this->gameInMatchFactory->confirmTeamsForGameInMatch($this->gameInMatch);
		}
		$this->patch = $patchRepo->findLatestPatchByPatchString($this->gameInMatch->game->gameData->gameVersion);
	}

	public function render(): string {
		$gameInMatch = $this->gameInMatch;
		if ($this->errorMessage !== null) return "<div style='grid-template-columns: 1fr' class=\"game-suggestion-details\" data-game-id=\"{$gameInMatch?->game->id}\">$this->errorMessage</div>";
		$patch = $this->patch;
		$selectable = $this->selectable;
		ob_start();
		include __DIR__.'/game-suggestion-details.template.php';
		return ob_get_clean();
	}

	public function __toString(): string {
		return $this->render();
	}
}