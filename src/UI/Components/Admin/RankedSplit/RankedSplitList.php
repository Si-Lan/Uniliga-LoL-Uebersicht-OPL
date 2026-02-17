<?php

namespace App\UI\Components\Admin\RankedSplit;

use App\Domain\Repositories\RankedSplitRepository;
use App\UI\Page\AssetManager;

class RankedSplitList {
	public function __construct(
		private ?RankedSplitRepository $rankedSplitRepo = null
	) {
		$this->rankedSplitRepo = $this->rankedSplitRepo ?? new RankedSplitRepository();
		AssetManager::addJsModule('admin/rankedSplits');
	}

	public function render(): string {
		$rankedSplits = $this->rankedSplitRepo->findAll();
		ob_start();
		include __DIR__.'/ranked-split-list.template.php';
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}
}