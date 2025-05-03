<?php

namespace App\Entity;

class Patch {
	public function __construct(
		public string $patchNumber,
		public bool $data = false,
		public bool $championWebp = false,
		public bool $itemWebp = false,
		public bool $spellWebp = false,
		public bool $runesWebp = false
	) {}
}