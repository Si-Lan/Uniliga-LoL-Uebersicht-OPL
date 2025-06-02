<?php

namespace App\UI\Components\Popups;

use App\UI\Page\AssetManager;

class Popup {
	public function __construct(
		private ?string $id = null,
		private ?string $pagePopupType = null,
		private bool $dismissable = true,
		private bool $noCloseButton = false,
		private bool $autoOpen = false,
		private string $content = '',
		private array $additionalClasses = [],
	) {
		if ($this->pagePopupType !== null) {
			$this->id = uniqid(str_replace('-','_', $this->pagePopupType)."_".$this->id);
		}
		AssetManager::addJsFile('/assets/js/components/popupDialogs.js');
	}

	public function getId(): string {
		return $this->id;
	}

	public function render(): string {
		$id = $this->id;
		$pagePopupType = $this->pagePopupType;
		$dismissable = $this->dismissable;
		$noCloseButton = $this->noCloseButton;
		$autoOpen = $this->autoOpen;
		$content = $this->content;
		$additionalClasses = $this->additionalClasses;
		ob_start();
		include __DIR__.'/popup.template.php';
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}
}