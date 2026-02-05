<?php

namespace App\UI\Components\UI;

use App\UI\Page\AssetManager;

class MultiSelectDropdown {
	public function __construct(
		private string $placeholder,
		private array $options,
		private array $selectedOptions = []
	) {
		AssetManager::addJsModule('components/multiSelectDropdown');
		AssetManager::addCssAsset('components/multiSelectDropdown.css');
	}

	public function render(): string {
		$placeholder = $this->placeholder;
		$options = $this->options;
		$selectedOptions = $this->selectedOptions;
		ob_start();
		include __DIR__.'/multi-select-dropdown.template.php';
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}
}