<?php

namespace App\Entities;

use App\Entities\ValueObjects\PlayerStats;

class PlayerInTournament {
	public PlayerStats $stats;
	/**
	 * @param array{top: int, jungle: int, middle: int, bottom: int, utility: int} $roles
	 * @param array<string, array{games: int, wins: int, kills: int, deaths: int, assists: int}> $champions
	 */
	public function __construct(
		public Player $player,
		public Tournament $tournament,
		array $roles,
		array $champions
	) {
		$this->stats = new PlayerStats($roles, $champions);
	}
}