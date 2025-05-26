<?php

namespace App\Domain\Entities;

use App\Domain\Entities\LolGame\GameData;

class Game {
	public ?GameData $gameData;
	public function __construct (
		public string $id,
		public ?array $rawMatchdata,
		public ?\DateTimeImmutable $playedAt
	) {
		$this->gameData = new GameData($this->rawMatchdata);
	}
}