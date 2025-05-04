<?php

namespace App\Entities;


class PlayerInTeamInTournament extends AbstractPlayerInTournament {
	/**
	 * @param array{top: int, jungle: int, middle: int, bottom: int, utility: int} $roles
	 * @param array<string, array{games: int, wins: int, kills: int, deaths: int, assists: int}> $champions
	 */
	public function __construct(
		public Player $player,
		public Team $team,
		public Tournament $tournament,
		public bool $removed,
		public array $roles,
		public array $champions
	) {
		parent::__construct($player, $tournament, $roles, $champions);
	}
}