<?php

namespace App\UI\Components\Admin\PatchData;

use App\Domain\Repositories\PatchRepository;
use App\Service\Updater\PatchUpdater;
use App\UI\Page\AssetManager;

class AddPatchesView {
	private array $types = ['new', 'missing', 'old'];
	public function __construct(
		private string $type = 'new',
		public bool $onlyRows = false,
		private ?PatchRepository $patchRepo = null,
		private ?PatchUpdater $patchUpdater = null
	) {
		$this->patchRepo = $this->patchRepo ?? new PatchRepository();
		$this->patchUpdater = $this->patchUpdater ?? new PatchUpdater();
		AssetManager::addJsAsset('admin/ddragonDownload.js');
		if (!in_array($this->type, $this->types)) {
			$this->type = 'new';
		}
	}
	public function render(): string {
		$onlyRows = $this->onlyRows;
		$localPatchnumbers = [];
		try {
			if ($this->type == 'new') {
				$patches = $this->patchUpdater->getNewPatchNumbersExternal();
			} else if ($this->type == 'missing') {
				$patches = $this->patchUpdater->getIntermediatePatchNumbersExternal();
				$localPatchnumbers = array_map(fn($patch) => $patch->patchNumber, $this->patchRepo->findAll());
			} else if ($this->type == 'old') {
				$patches = $this->patchUpdater->getOldPatchNumbersExternal();
			}
		} catch (\Exception $e) {
			return "Fehler beim Abrufen der Patchdaten.";
		}
		ob_start();
		include __DIR__.'/add-patches-view.template.php';
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}
}