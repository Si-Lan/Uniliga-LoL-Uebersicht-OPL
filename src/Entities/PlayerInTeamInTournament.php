<?php

namespace App\Entities;


class PlayerInTeamInTournament {
	/**
	 * @param array{top: int, jungle: int, middle: int, bottom: int, utility: int} $roles
	 * @param array<string, array{games: int, wins: int, kills: int, deaths: int, assists: int}> $champions
	 */
	public function __construct(
		public Player $player,
		public Team $team,
		public bool $removed,
		public array $roles,
		public array $champions
	) {}


	/**
	 * @param 'ASC'|'DESC' $direction
	 * @return array<string, array{games: int, wins: int, kills: int, deaths: int, assists: int}>
	 */
	public function getChampionsSorted(string $direction="DESC"):array {
		$sorted_champions = $this->champions;
		arsort($sorted_champions);
		if ($direction == "ASC") $sorted_champions = array_reverse($sorted_champions);
		return $sorted_champions;
	}

	/**
	 * @return array<string, array{games: int, wins: int, kills: int, deaths: int, assists: int}>
	 */
	public function getTopChampions(int $amount=5):array {
		return array_slice($this->getChampionsSorted(), 0, $amount);
	}
}