<?php

namespace App\Domain\Entities;

use App\Domain\Entities\ValueObjects\RankAverage;

class Team {
	public function __construct(
		public int $id,
		public string $name,
		public ?string $shortName,
		public ?int $logoId,
		public ?\DateTimeImmutable $lastLogoDownload,
		public RankAverage $rank
	) {}

	public function equals(Team $team):bool {
		return ($this->id === $team->id);
	}

	public function getLogoUrl(bool $squared = false) : string|false {
		$squareAddition = $squared ? "_square" : "";
		if (is_null($this->logoId)) return false;
		$baseUrl = "/assets/img/team_logos/{$this->logoId}/";
		if (!file_exists(BASE_PATH.'/public'.$baseUrl)) return false;
		if (isset($_COOKIE['lightmode']) && $_COOKIE['lightmode'] === "1") {
			return $baseUrl."logo_light$squareAddition.webp";
		} else {
			return $baseUrl."logo$squareAddition.webp";
		}
	}

	public function hasRank(): bool {
		return $this->rank->rankTier !== null;
	}

	public function getDataDifference(Team $team): array {
		$diffToOther = [];
		if ($this->id !== $team->id) $diffToOther['id'] = $this->id;
		if ($this->name !== $team->name) $diffToOther['name'] = $this->name;
		if ($this->shortName !== $team->shortName) $diffToOther['shortName'] = $this->shortName;
		if ($this->logoId !== $team->logoId) $diffToOther['logoId'] = $this->logoId;
		if ($this->lastLogoDownload?->format("Y-m-d") !== $team->lastLogoDownload?->format("Y-m-d")) $diffToOther['lastLogoDownload'] = $this->lastLogoDownload?->format("Y-m-d");
		if ($this->rank->rankTier !== $team->rank->rankTier) $diffToOther['rankTier'] = $this->rank->rankTier;
		if ($this->rank->rankDiv !== $team->rank->rankDiv) $diffToOther['rankDiv'] = $this->rank->rankDiv;
		if ($this->rank->rankNum !== $team->rank->rankNum) $diffToOther['rankNum'] = $this->rank->rankNum;
		return $diffToOther;
	}
}