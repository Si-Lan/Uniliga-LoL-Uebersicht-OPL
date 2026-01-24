<?php

namespace App\UI\Components\Admin\TournamentEdit;

use App\Domain\Entities\Tournament;
use App\Domain\Repositories\RankedSplitRepository;
use App\UI\Page\AssetManager;

class TournamentEditForm {
	private array $rankedSplits;
	public function __construct(
		private Tournament $tournament,
		private bool $isNew = false,
		private array $parentIds = [],
		private array $childrenIds = []
	) {
		$rankedSplitRepo = new RankedSplitRepository();
		$this->rankedSplits = $rankedSplitRepo->findAll();
		AssetManager::addJsFile('/assets/js/admin/oplImport.js');
	}

	public function render(): string {
		$tournament = $this->tournament;
		$isNew = $this->isNew;
		$parentIds = $this->parentIds;
		$childrenIds = $this->childrenIds;
		$rankedSplits = $this->rankedSplits;
		ob_start();
		include __DIR__.'/tournament-edit-form.template.php';
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}
}