<?php

namespace App\Entities;

use App\Entities\LolGame\GameData;

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