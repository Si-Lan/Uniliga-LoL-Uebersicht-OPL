<?php

namespace App\Entities;

class Matchup {
	public function __construct(
		public int $id,
		public Tournament $tournamentStage,
		public ?Team $team1,
		public ?Team $team2,
		public ?string $team1Score,
		public ?string $team2Score,
		public ?\DateTimeImmutable $plannedDate,
		public ?int $playday,
		public ?int $bestOf,
		public bool $played,
		public ?int $winnerId,
		public ?int $loserId,
		public bool $draw,
		public bool $defWin
	) {}
}