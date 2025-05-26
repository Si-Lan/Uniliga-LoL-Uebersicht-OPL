<?php

namespace App\Domain\Entities;

class TeamInTournamentStage {
	public function __construct(
		public Team $team,
		public Tournament $tournamentStage,
		public TeamInTournament $teamInRootTournament,
		public ?int $standing,
		public ?int $played,
		public ?int $wins,
		public ?int $draws,
		public ?int $losses,
		public ?int $points,
		public ?int $singleWins,
		public ?int $singleLosses
	) {}

	public function getWinsLosses():string {
		if ($this->tournamentStage->mostCommonBestOf === null || $this->tournamentStage->mostCommonBestOf % 2 == 1) {
			return "{$this->wins}-{$this->losses}";
		} else {
			return "{$this->wins}-{$this->draws}-{$this->losses}";
		}
	}
}