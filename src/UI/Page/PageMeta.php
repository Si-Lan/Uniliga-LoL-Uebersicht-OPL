<?php

namespace App\UI\Page;

class PageMeta {
	public string $shortTitle;
	public function __construct(
		public string $title = '',
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
		AssetManager::addCssAsset('design2.css');
		AssetManager::addJsAsset('jquery-3.7.1.min.js');
		AssetManager::addJsModuleAsset('main.js');
		$this->bodyDataId = "data-id='{$this->bodyDataId}'";
	}
}