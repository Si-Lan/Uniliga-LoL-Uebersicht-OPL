<?php

namespace App\UI\Components\UI;

class DropdownCustom {
	public function __construct(
		private string $type,
		private array $items
	) {}

	public function render(): string {
		$type = $this->type;
		$items = $this->items;
		ob_start();
		include __DIR__.'/dropdown-custom.template.php';
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}
}