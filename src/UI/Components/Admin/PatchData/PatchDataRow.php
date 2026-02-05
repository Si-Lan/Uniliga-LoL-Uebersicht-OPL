<?php

namespace App\UI\Components\Admin\PatchData;

use App\Domain\Entities\Patch;
use App\UI\Page\AssetManager;

class PatchDataRow {

	public function __construct(
		private Patch $patch
	) {
		AssetManager::addJsModule('admin/ddragonDownload');
	}
	public function render(): string {
		$patch = $this->patch;
		ob_start();
		include __DIR__.'/patch-data-row.template.php';
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}
}