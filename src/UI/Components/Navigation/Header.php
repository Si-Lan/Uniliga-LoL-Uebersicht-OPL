<?php

namespace App\UI\Components\Navigation;

use App\Domain\Entities\Tournament;
use App\UI\Enums\HeaderType;
use App\UI\Page\AssetManager;

class Header {
	public function __construct(
		private HeaderType $type = HeaderType::DEFAULT,
		private ?Tournament $tournament = null
	) {
		AssetManager::addJsModule('components/header');
	}

	public function render(): string {
		$type = $this->type;
		$tournament = $this->tournament;
		ob_start();
		include __DIR__.'/header.template.php';
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}
}