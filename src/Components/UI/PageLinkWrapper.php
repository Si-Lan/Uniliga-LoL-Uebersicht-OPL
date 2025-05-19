<?php

namespace App\Components\UI;

use App\Components\Helpers\IconRenderer;

class PageLinkWrapper {
	public string $classes;

	public function __construct(
		public string $href,
		public array $additionalClasses = [],
		public string $content = ''
	) {
		$this->classes = implode(' ', array_filter(["page-link", ...$additionalClasses]));
	}

	public function render(): string {
		ob_start();
		include __DIR__.'/page-link-wrapper.template.php';
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}

	public static function makeTarget(string $linkText, bool $withoutIcon = false, string $icon = 'chevron_right'): string {
		$linkIcon = $withoutIcon ? '' : IconRenderer::getMaterialIconSpan($icon,["page-link-icon"]);
		return <<<HTML
	<span class="page-link-target">
    	{$linkText}
    	{$linkIcon}
	</span>
HTML;
	}
}