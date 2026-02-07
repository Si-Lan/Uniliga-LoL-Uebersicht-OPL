<?php

namespace App\Domain\Entities;

use App\Domain\Enums\SuggestionStatus;

class MatchupChangeSuggestion {
	/**
	 * @param int|null $id
	 * @param Matchup $matchup
	 * @param string|null $customTeam1Score
	 * @param string|null $customTeam2Score
	 * @param array<GameInMatch> $games
	 * @param SuggestionStatus $status
	 * @param \DateTimeImmutable|null $createdAt
	 * @param \DateTimeImmutable|null $finishedAt
	 */
	public function __construct(
		public ?int $id,
		public Matchup $matchup,
		public ?string $customTeam1Score,
		public ?string $customTeam2Score,
		public array $games,
		public SuggestionStatus $status,
		public readonly ?\DateTimeImmutable $createdAt,
		public ?\DateTimeImmutable $finishedAt,
	) {}

	public function accept(): void {
		$this->status = SuggestionStatus::ACCEPTED;
		$this->finishedAt = new \DateTimeImmutable();
	}

	public function reject(): void {
		$this->status = SuggestionStatus::REJECTED;
		$this->finishedAt = new \DateTimeImmutable();
	}

	public function addGame(GameInMatch $game): void {
		$this->games[] = $game;
	}

	public function hasScoreChange(): bool {
		return $this->customTeam1Score !== null || $this->customTeam2Score !== null;
	}
}