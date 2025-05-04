<?php

namespace App\Entities;

abstract class AbstractPlayerInTournament {
	/**
	 * @param array{top: int, jungle: int, middle: int, bottom: int, utility: int} $roles
	 * @param array<string, array{games: int, wins: int, kills: int, deaths: int, assists: int}> $champions
	 */
	public function __construct(
		public Player $player,
		public Tournament $tournament,
		public array $roles,
		public array $champions
	) {}

	/**
	 * @param 'ASC'|'DESC' $direction
	 * @return array<string, array{games: int, wins: int, kills: int, deaths: int, assists: int}>
	 */
	public function getChampionsSorted(string $direction="DESC"):array {
		$sorted_champions = $this->champions;
		uasort($sorted_champions, fn($a, $b) => $b['games'] <=> $a['games']);
		if ($direction == "ASC") $sorted_champions = array_reverse($sorted_champions,true);
		return $sorted_champions;
	}

	/**
	 * @return array<string, array{games: int, wins: int, kills: int, deaths: int, assists: int}>
	 */
	public function getTopChampions(int $amount=5):array {
		return array_slice($this->getChampionsSorted(), 0, $amount);
	}
}