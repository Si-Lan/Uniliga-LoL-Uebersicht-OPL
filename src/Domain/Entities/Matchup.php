<?php

namespace App\Domain\Entities;

class Matchup {
	public function __construct(
		public int $id,
		public Tournament $tournamentStage,
		public ?TeamInTournament $team1,
		public ?TeamInTournament $team2,
		public ?string $team1Score,
		public ?string $team2Score,
		public ?\DateTimeImmutable $plannedDate,
		public ?int $playday,
		public ?int $bestOf,
		public bool $played,
		public ?int $winnerId,
		public ?int $loserId,
		public bool $draw,
		public bool $defWin,
		public bool $hasCustomScore,
		public ?string $customTeam1Score,
		public ?string $customTeam2Score,
		public bool $hasCustomGames
	) {}

	public static function createEmpty(Tournament $tournamentStage): Matchup {
		return self::createEmptyWithId(0, $tournamentStage);
	}
	public static function createEmptyWithId(int $id, Tournament $tournamentStage): Matchup {
		return new Matchup(
			id: $id,
			tournamentStage: $tournamentStage,
			team1: null,
			team2: null,
			team1Score: null,
			team2Score: null,
			plannedDate: null,
			playday: null,
			bestOf: null,
			played: false,
			winnerId: null,
			loserId: null,
			draw: false,
			defWin: false,
			hasCustomScore: false,
			customTeam1Score: null,
			customTeam2Score: null,
			hasCustomGames: false
		);
	}

	public int $bracketColumn = 0;
	public array $bracketPrevMatchups = [];
	public array $bracketNextMatchups = [];

	public function getTeam1Score(): ?string {
		return $this->hasCustomScore ? $this->customTeam1Score : $this->team1Score;
	}
	public function getTeam2Score(): ?string {
		return $this->hasCustomScore ? $this->customTeam2Score : $this->team2Score;
	}

	public function getTeam1Result(): ?string {
		if ($this->isQualified()) return 'qualified';
		if ($this->team1 === null || !$this->played) return null;
		if ($this->draw) return 'draw';
		if ($this->winnerId === $this->team1->team->id) return 'win';
		return 'loss';
	}
	public function getTeam2Result(): ?string {
		if ($this->isQualified()) return 'qualified';
		if ($this->team1 === null || !$this->played) return null;
		if ($this->draw) return 'draw';
		if ($this->winnerId === $this->team1->team->id) return 'loss';
		return 'win';
	}

	public function isQualified(): bool {
		return $this->getTeam1Score() === 'Q' || $this->getTeam2Score() === 'Q';
	}
}