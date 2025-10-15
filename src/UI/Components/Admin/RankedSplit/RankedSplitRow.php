<?php

namespace App\UI\Components\Admin\RankedSplit;

use App\Domain\Entities\RankedSplit;

class RankedSplitRow {
	public function __construct(
		private ?RankedSplit $rankedSplit = null,
	) {}

	public function render(): string {
		$rankedSplit = $this->rankedSplit;
		ob_start();
		include __DIR__.'/ranked-split-row.template.php';
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}
}