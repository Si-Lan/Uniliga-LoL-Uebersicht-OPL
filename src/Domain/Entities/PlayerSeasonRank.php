<?php

namespace App\Domain\Entities;

use App\Domain\Entities\ValueObjects\RankForPlayer;

class PlayerSeasonRank {
	public function __construct(
		public Player $player,
		public RankedSplit $rankedSplit,
		public RankForPlayer $rank,
	) {}
}