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
	 * @param int|null $logoHistoryDir
	 * @param string $nameInTournament
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
		private ?int $logoHistoryDir,
		public string $nameInTournament
	) {}

	public function getLogoUrl() : string|bool {
		if (is_null($this->team->logoId)) return false;
		if (is_null($this->logoHistoryDir)) return false;
		if ($this->logoHistoryDir < 0) return $this->team->getLogoUrl();
		$baseUrl = "/img/team_logos/{$this->team->logoId}/{$this->logoHistoryDir}/";
		if (isset($_COOKIE['lightmode']) && $_COOKIE['lightmode'] === "1") {
			return $baseUrl."logo_light.webp";
		} else {
			return $baseUrl."logo.webp";
		}
	}
}