<?php

namespace App\Domain\Entities;

use App\Domain\Entities\ValueObjects\RankAverage;

class TeamSeasonRankInTournament {
	public function __construct(
		public Team $team,
		public Tournament $tournament,
		public RankedSplit $rankedSplit,
		public RankAverage $rank,
	) {}

	public function isSelectedByUser():bool {
		return $this->tournament->userSelectedRankedSplit->equals($this->rankedSplit);
	}
	public function hasRank(): bool {
		return $this->rank->rankTier !== null;
	}
}