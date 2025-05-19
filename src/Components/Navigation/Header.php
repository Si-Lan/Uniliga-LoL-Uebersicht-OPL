<?php

namespace App\Components\Navigation;

use App\Entities\Tournament;
use App\Enums\HeaderType;

class Header {
	public function __construct(
		private HeaderType $type = HeaderType::DEFAULT,
		private ?Tournament $tournament = null
	) {}

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