<?php

namespace App\Entities;

use App\Entities\ValueObjects\RankForPlayer;

class Player {
	/**
	 * @param array<string> $matchesGotten
	 */
	public function __construct(
		public int $id,
		public string $name,
		public ?string $riotIdName,
		public ?string $riotIdTag,
		public ?string $summonerName,
		public ?string $summonerId,
		public ?string $puuid,
		public RankForPlayer $rank,
		public array $matchesGotten
	) {}

	public function getFullRiotID(): string {
		return $this->riotIdName."#".$this->riotIdTag;
	}
	public function getEncodedRiotID(): string {
		return urlencode($this->riotIdTag??"")."-".urlencode($this->riotIdTag??"");
	}
	public function getRiotIdTagWithPrefix(): string {
		return (($this->riotIdTag??"") != "") ? "#".$this->riotIdTag : "";
	}
}