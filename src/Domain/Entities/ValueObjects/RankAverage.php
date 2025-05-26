<?php

namespace App\Domain\Entities\ValueObjects;

class RankAverage extends Rank {
	public function __construct(
		public ?string $rankTier,
		public ?string $rankDiv,
		public ?float $rankNum
	) {
		parent::__construct($rankTier, $rankDiv);
	}

	public function getRankWithEloNum(): string {
		if (is_null($this->rankTier)) return "";
		$roundedNum = round($this->rankNum,2);
		if (in_array($this->rankTier, self::APEX_TIERS)) {
			return $this->getRankTier()." (".$roundedNum.")";
		}
		return $this->getRankTier()." ".$this->rankDiv." (".$roundedNum.")";
	}
}