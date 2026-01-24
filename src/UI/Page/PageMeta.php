<?php

namespace App\UI\Page;

class PageMeta {
	public string $shortTitle;
	public function __construct(
		public string $title = '',
		public array $css = [],
		public array $js = [],
		public string $bodyClass = '',
		public string $bodyDataId = ''
	) {
		if ($title) {
			$this->shortTitle = $title;
			$this->title .= ' | Uniliga LoL - Übersicht';
		} else {
			$this->title = 'Uniliga LoL - Übersicht';
			$this->shortTitle = $this->title;
		}
		$this->css[] = 'design2';
		$this->js[] = 'jquery-3.7.1.min';
		$this->js[] = 'main';
		AssetManager::addJsFile('/assets/js/fragmentLoader.js');
		$this->bodyDataId = "data-id='{$this->bodyDataId}'";
	}
}