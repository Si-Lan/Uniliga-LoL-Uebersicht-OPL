<?php

namespace App\UI\Components\Player;

use App\Domain\Entities\Player;
use App\UI\Page\AssetManager;

class PlayerSearchCard {
	public function __construct(
		private Player $player,
		private bool $removeFromRecents = false
	) {
		AssetManager::addJsFile('/assets/js/components/popups.js');
	}

	public function render(): string {
		$player = $this->player;
		$removeFromRecents = $this->removeFromRecents;
		ob_start();
		include __DIR__.'/player-search-card.template.php';
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}
}