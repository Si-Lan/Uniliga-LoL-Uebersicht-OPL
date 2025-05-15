<?php

namespace App\Components\Cards;

use App\Entities\Team;
use App\Entities\TeamInTournament;
use App\Repositories\PlayerInTeamInTournamentRepository;
use App\Repositories\PlayerInTeamRepository;
use App\Utilities\EntitySorter;

class SummonerCardContainer {
	private string $summonerCardHtml = '';
	public function __construct(
		public Team|TeamInTournament $team
	) {
		if ($team instanceof TeamInTournament) {
			$playerInTeamInTournamentRepo = new PlayerInTeamInTournamentRepository();
			$playersInTeamInTournament = $playerInTeamInTournamentRepo->findAllByTeamAndTournament($team->team, $team->tournament);
			$playersInTeamInTournament = EntitySorter::sortPlayersByAllRoles($playersInTeamInTournament);
			foreach ($playersInTeamInTournament as $playerInTeamInTournament) {
				$this->summonerCardHtml .= new SummonerCard($playerInTeamInTournament);
			}
		}
		if ($team instanceof Team) {
			$playerInTeamRepo = new PlayerInTeamRepository();
			$playersInTeam = $playerInTeamRepo->findAllByTeam($team);
			foreach ($playersInTeam as $playerInTeam) {
				$this->summonerCardHtml .= new SummonerCard($playerInTeam);
			}
		}
	}

	public function render(): string {
		return "<div class='summoner-card-container'>{$this->summonerCardHtml}</div>";
	}
	public function __toString(): string {
		return $this->render();
	}
}