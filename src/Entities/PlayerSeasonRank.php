<?php

namespace App\Entities;

use App\Entities\ValueObjects\RankForPlayer;

class PlayerSeasonRank {
	public function __construct(
		public Player $player,
		public RankedSplit $rankedSplit,
		public RankForPlayer $rank,
	) {}
}