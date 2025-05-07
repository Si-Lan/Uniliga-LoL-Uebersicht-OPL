<?php

namespace App\Entities;

class TeamInTournament {
	/**
	 * @param Team $team
	 * @param Tournament $tournament
	 * @param array<string, array{games: int, wins: int}> $champsPlayed
	 * @param array<string, int> $champsBanned
	 * @param array<string, int> $champsPlayedAgainst
	 * @param array<string, int> $champsBannedAgainst
	 * @param int|null $gamesPlayed
	 * @param int|null $gamesWon
	 * @param int|null $avgWinTime
	 */
	public function __construct(
		public Team $team,
		public Tournament $tournament,
		public ?array $champsPlayed,
		public ?array $champsBanned,
		public ?array $champsPlayedAgainst,
		public ?array $champsBannedAgainst,
		public ?int $gamesPlayed,
		public ?int $gamesWon,
		public ?int $avgWinTime
	) {}
}