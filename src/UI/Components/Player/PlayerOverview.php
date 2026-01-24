<?php

namespace App\UI\Components\Player;

use App\Domain\Entities\Player;
use App\Domain\Repositories\PlayerInTeamInTournamentRepository;

class PlayerOverview {
	private PlayerInTeamInTournamentRepository $playerInTeamInTournamentRepo;
	public function __construct(
		private Player $player,
		private bool $showPlayerPageLink = true
	) {
		$this->playerInTeamInTournamentRepo = new PlayerInTeamInTournamentRepository();
	}

	public function render(): string {
		$playerInTeamsInTournaments = $this->playerInTeamInTournamentRepo->findAllByPlayer($this->player);
		$player = $this->player;
		$showPlayerPageLink = $this->showPlayerPageLink;
		ob_start();
		include __DIR__.'/player-overview.template.php';
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}
}