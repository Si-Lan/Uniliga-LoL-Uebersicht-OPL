<?php

namespace App\UI\Components\Admin;

use App\Domain\Entities\Tournament;
use App\UI\Page\AssetManager;

class TournamentEditForm {
	public function __construct(
		private Tournament $tournament,
		private bool $isNew = false
	) {
		AssetManager::addJsFile('assets/js/admin/oplImport.js');
	}

	public function render(): string {
		$tournament = $this->tournament;
		$isNew = $this->isNew;
		ob_start();
		include __DIR__.'/tournament-edit-form.template.php';
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}
}