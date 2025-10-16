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

	public array $winsVsById = [];

	public function getWinsLosses():string {
		if ($this->tournamentStage->mostCommonBestOf === null || $this->tournamentStage->mostCommonBestOf % 2 == 1) {
			return "{$this->wins}-{$this->losses}";
		} else {
			return "{$this->wins}-{$this->draws}-{$this->losses}";
		}
	}

	public function resetStandings(): void {
		$this->standing = null;
		$this->played = 0;
		$this->wins = 0;
		$this->draws = 0;
		$this->losses = 0;
		$this->points = 0;
		$this->singleWins = 0;
		$this->singleLosses = 0;
	}

	public function addMatchupResultToStanding(Matchup $matchup): void {
		if (!$matchup->played) return;

		switch ($this->team->id) {
			case $matchup->team1->team->id:
				$enemyId = $matchup->team2->team->id;
				$currentTeam = 1;
				break;
			case $matchup->team2->team->id:
				$enemyId = $matchup->team1->team->id;
				$currentTeam = 2;
				break;
			default:
				return;
		}

		if (!array_key_exists($enemyId, $this->winsVsById)) {
			$this->winsVsById[$enemyId] = 0;
		}

		$this->played++;

		$currentTeamScore = $currentTeam === 1 ? $matchup->team1Score : $matchup->team2Score;
		$enemyTeamScore = $currentTeam === 1 ? $matchup->team2Score : $matchup->team1Score;

		if ($matchup->winnerId === $this->team->id) {
			$this->wins++;
			$this->winsVsById[$enemyId] += intval($currentTeamScore);
			$pointsToAdd = match ($matchup->bestOf) {
				1 => 1,
				default => 2,
			};
			if (is_numeric($currentTeamScore) || $currentTeamScore == "W") {
				$this->points += $pointsToAdd;
			}
		}
		if (is_numeric($currentTeamScore)) {
			$this->singleWins += intval($currentTeamScore);
		}
		if ($matchup->draw) {
			$this->draws++;
			$this->points += 1;
		}
		if ($matchup->loserId === $this->team->id) {
			$this->losses++;
		}
		if (is_numeric($enemyTeamScore)) {
			$this->singleLosses += intval($enemyTeamScore);
		}
	}
}