<?php

namespace App\Entities;

class GameInMatch {
	public function __construct(
		public Game $game,
		public Matchup $matchup,
		public ?TeamInTournament $blueTeam,
		public ?TeamInTournament $redTeam,
		public bool $oplConfirmed
	) {}

	public function getWinningTeam(): ?TeamInTournament {
		if ($this->game->gameData->blueTeamWin) {
			return $this->blueTeam;
		} elseif ($this->game->gameData->redTeamWin) {
			return $this->redTeam;
		} else {
			return null;
		}
	}
	public function getLosingTeam(): ?TeamInTournament {
		if ($this->game->gameData->blueTeamWin) {
			return $this->redTeam;
		} elseif ($this->game->gameData->redTeamWin) {
			return $this->blueTeam;
		} else {
			return null;
		}
	}
}