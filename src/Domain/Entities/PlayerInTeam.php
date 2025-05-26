<?php

namespace App\Domain\Entities;

class PlayerInTeam {
	public function __construct(
		public Player $player,
		public Team $team,
		public bool $removed
	) {}
}