<?php

namespace App\Entities;

use App\Entities\ValueObjects\RankAverage;

class Team {
	public function __construct(
		public int $id,
		public string $name,
		public ?string $shortName,
		public ?string $logoUrl,
		public ?int $logoId,
		public ?\DateTimeImmutable $lastLogoDownload,
		public RankAverage $avgRank
	) {}

	public function getLogoUrlForColorMode() : ?string {
		if (is_null($this->logoUrl)) return null;
		$baseUrl = "/img/team_logos/{$this->logoId}/";
		if (isset($_COOKIE['lightmode']) && $_COOKIE['lightmode'] === "1") {
			return $baseUrl."logo_light.webp";
		} else {
			return $baseUrl."logo.webp";
		}
	}
}