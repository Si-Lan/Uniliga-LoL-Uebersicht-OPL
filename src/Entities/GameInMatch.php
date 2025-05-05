<?php

namespace App\Entities;

class GameInMatch {
	public function __construct(
		public Game $game,
		public Matchup $matchup,
		public ?Team $blueTeam,
		public ?Team $redTeam,
		public bool $oplConfirmed
	) {}
}