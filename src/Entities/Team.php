<?php

namespace App\Entities;

class Team {
	public function __construct(
		public int $id,
		public string $name,
		public ?string $shortName,
		public ?string $logoUrl,
		public ?int $logoId,
		public ?\DateTimeImmutable $lastLogoDownload,
		public ?string $avgRankTier,
		public ?string $avgRankDiv,
		public ?string $avgRankNum,
	) {}
}