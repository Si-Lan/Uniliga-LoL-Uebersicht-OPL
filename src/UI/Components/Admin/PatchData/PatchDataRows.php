<?php

namespace App\UI\Components\Admin\PatchData;

use App\Domain\Repositories\PatchRepository;

class PatchDataRows {
	public function __construct(
		private ?PatchRepository $patchRepo = null
	) {
		$this->patchRepo = $this->patchRepo ?? new PatchRepository();
	}

	public function render(): string {
		$patches = $this->patchRepo->findAll();
		$patches = array_reverse($patches);
		$result = "";
		foreach ($patches as $patch) {
			$result .= new PatchDataRow($patch);
		}
		return $result;
	}
	public function __toString(): string {
		return $this->render();
	}
}