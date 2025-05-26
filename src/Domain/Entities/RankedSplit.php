<?php

namespace App\Domain\Entities;

class RankedSplit {
	public function __construct(
		public int $season,
		public int $split,
		public \DateTimeImmutable $dateStart,
		public ?\DateTimeImmutable $dateEnd
	) {}

	public function getName(bool $trailingZero = true): string {
		if (!$trailingZero && $this->split === 0) {
			return $this->season;
		}
		return $this->season."-".($this->split??0);
	}
	public function getPrettyName(): string {
		return "Season ".$this->season." ".(($this->split > 0) ? "Split $this->split" : "");
	}
	public function equals(RankedSplit $rankedSplit): bool {
		return $this->season == $rankedSplit->season && $this->split == $rankedSplit->split;
	}
}