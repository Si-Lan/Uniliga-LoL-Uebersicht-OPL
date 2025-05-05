<?php

namespace App\Entities\ValueObjects;

class RankForPlayer extends Rank {
	public function __construct(
		public ?string $rankTier,
		public ?string $rankDiv,
		public ?int $rankLp
	) {
		parent::__construct($rankTier, $rankDiv);
	}

	public function getRank(): string {
		if (is_null($this->rankTier)) return "";
		if (in_array($this->rankTier, self::APEX_TIERS)) {
			return $this->getRankTier()." (".$this->rankLp." LP)";
		}
		return $this->getRankTier()." ".$this->rankDiv;
	}
}