<?php

namespace App\UI\Components\UI;

class PageLink {
	public string $classes;
	public function __construct(
		public string $href,
		public string $text,
		array $additionalClasses = [],
		public ?string $materialIcon = null,
		public string $linkIcon = 'chevron_right',
		public bool $newTab = false
	) {
		$this->classes = implode(' ', array_filter(["page-link", ($this->materialIcon !== null)?'icon-link':'', ...$additionalClasses]));
		if ($this->linkIcon === "open_in_new") $this->classes .= " external-link";
	}

	public function render(): string {
		ob_start();
		include __DIR__.'/page-link.template.php';
		return ob_get_clean();
	}

	public function __toString(): string {
		return $this->render();
	}
}