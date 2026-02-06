<?php

namespace App\Domain\Entities;

use App\Domain\Enums\SuggestionStatus;

class MatchupChangeSuggestions {
	/**
	 * @param int|null $id
	 * @param Matchup $matchup
	 * @param string|null $customTeam1Score
	 * @param string|null $customTeam2Score
	 * @param array<Game> $addedGames
	 * @param array<Game> $removedGames
	 * @param SuggestionStatus $status
	 */
	public function __construct(
		public ?int $id,
		public Matchup $matchup,
		public ?string $customTeam1Score,
		public ?string $customTeam2Score,
		public array $addedGames,
		public array $removedGames,
		public SuggestionStatus $status
	) {}

	public function accept(): void {
		$this->status = SuggestionStatus::ACCEPTED;
	}

	public function reject(): void {
		$this->status = SuggestionStatus::REJECTED;
	}

	public function addGame(Game $game): void {
		$this->addedGames[] = $game;
	}
	public function removeGame(Game $game): void {
		$this->removedGames[] = $game;
	}
}