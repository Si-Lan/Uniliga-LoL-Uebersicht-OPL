<?php

namespace App\Components;

use App\Entities\Team;
use App\Entities\TeamInTournament;
use App\Repositories\PlayerInTeamInTournamentRepository;
use App\Repositories\PlayerInTeamRepository;

class MultiOpggButton {
	private string $opggUrl = 'https://www.op.gg/multisearch/euw?summoners=';
	private int $playerAmount = 0;
	public function __construct(
		public Team|TeamInTournament $team,
	) {
		if ($team instanceof Team) {
			$playerInTeamRepo = new PlayerInTeamRepository();
			$players = $playerInTeamRepo->findAllByTeam($team);
		}
		if ($team instanceof TeamInTournament) {
			$playerInTeamInTournamentRepo = new PlayerInTeamInTournamentRepository();
			$players = $playerInTeamInTournamentRepo->findAllByTeamAndTournament($team->team, $team->tournament);
		}
		foreach ($players as $i=>$player) {
			if ($player->removed) continue;
			if ($player->player->riotIdName == null) continue;
			if ($i != 0) {
				$this->opggUrl .= urlencode(",");
			}
			$this->opggUrl .= urlencode($player->player->getFullRiotID());
			$this->playerAmount++;
		}
	}

	public function render(): string {
		$opggUrl = $this->opggUrl;
		$playerAmount = $this->playerAmount;
		ob_start();
		include __DIR__.'/multi-opgg-button.template.php';
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}
}