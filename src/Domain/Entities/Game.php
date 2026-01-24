<?php

namespace App\Domain\Entities;

use App\Domain\Entities\LolGame\GameData;

class Game {
	public ?GameData $gameData = null;
	public function __construct (
		public string $id,
		public ?array $rawMatchdata,
		public ?\DateTimeImmutable $playedAt
	) {
		if ($this->rawMatchdata !== null) $this->gameData = new GameData($this->rawMatchdata);
	}
}