<?php

namespace App\Entities;

class Game {
	public function __construct (
		public string $id,
		public ?array $matchdata,
		public ?\DateTimeImmutable $playedAt
	) {}
}