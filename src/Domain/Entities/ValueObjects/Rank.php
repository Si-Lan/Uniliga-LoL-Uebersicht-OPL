<?php

namespace App\Domain\Entities\ValueObjects;

class Rank {
	protected const array APEX_TIERS = ["CHALLENGER", "GRANDMASTER", "MASTER"];
	public function __construct(
		public ?string $rankTier,
		public ?string $rankDiv
	) {}

	public function getRankTier(): string {
		if (is_null($this->rankTier)) return "";
		return ucfirst(strtolower($this->rankTier));
	}

	public function getRankTierLowercase(): string {
		if (is_null($this->rankTier)) return "";
		return strtolower($this->rankTier);
	}

	public function getRank(): string {
		if (is_null($this->rankTier)) return "";
		if (in_array($this->rankTier, self::APEX_TIERS)) {
			return $this->getRankTier();
		}
		return $this->getRankTier()." ".$this->rankDiv;
	}

    public function isRank(): bool {
        return $this->rankTier !== null && $this->rankTier !== "UNRANKED";
    }

    public function getValue(): int {
        return RankMapper::getValue($this);
    }
}