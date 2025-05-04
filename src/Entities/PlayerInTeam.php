<?php

namespace App\Entities;

class PlayerInTeam {
	public function __construct(
		public Player $player,
		public Team $team,
		public bool $removed
	) {}
}