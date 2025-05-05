<?php

namespace App\Entities;

class TeamInTournamentStage {
	public function __construct(
		public Team $team,
		public Tournament $tournamentStage,
		public ?int $standing,
		public ?int $played,
		public ?int $wins,
		public ?int $draws,
		public ?int $losses,
		public ?int $points,
		public ?int $singleWins,
		public ?int $singleLosses
	) {}
}