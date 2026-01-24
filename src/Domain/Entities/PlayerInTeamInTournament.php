<?php

namespace App\Domain\Entities;


use App\Domain\Entities\ValueObjects\PlayerStats;

class PlayerInTeamInTournament {
	public PlayerStats $stats;
	/**
	 * @param array{top: int, jungle: int, middle: int, bottom: int, utility: int} $roles
	 * @param array<string, array{games: int, wins: int, kills: int, deaths: int, assists: int}> $champions
	 */
	public function __construct(
		public Player $player,
		public TeamInTournament $teamInTournament,
		public bool $removed,
		array $roles,
		array $champions,
	) {
		$this->stats = new PlayerStats($roles, $champions);
	}
}