<?php

namespace App\Entities;

use App\Entities\ValueObjects\RankAverage;

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
	 * @param array<RankAverage> $ranks
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
		public ?int $avgWinTime,
		public array $ranks
	) {}

	public function hasAnAvgRank():bool {
		foreach ($this->ranks as $rank) {
			if ($rank->rankTier !== null) return true;
		}
		return false;
	}
}