<?php

namespace App\Entity;

class PlayerSeasonRank {
	public function __construct(
		public int $playerId,
		public int $season,
		public int $split,
		public RankedSplit $rankedSplit,
		public ?string $rankTier,
		public ?string $rankDiv,
		public ?int $rankLp
	) {}

	public function getRankTierUC(): string {
		return ucfirst(strtolower($this->rankTier));
	}
	public function getRankTierLC(): string {
		return strtolower($this->rankTier);
	}

	public function getFullRank(): string {
		$tier = ucfirst(strtolower($this->rankTier));

		if (in_array($this->rankTier, ["CHALLENGER", "GRANDMASTER", "MASTER"])) {
			return "{$tier} ({$this->rankLp} LP)";
		}

		return "{$tier} {$this->rankDiv}";
	}
}