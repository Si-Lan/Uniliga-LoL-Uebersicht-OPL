<?php

namespace App\Entities;

use App\Entities\ValueObjects\RankForPlayer;

class PlayerSeasonRank {
	public function __construct(
		public int $playerId,
		public RankedSplit $rankedSplit,
		public RankForPlayer $rank,
	) {}
}