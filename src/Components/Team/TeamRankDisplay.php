<?php

namespace App\Components\Team;

use App\Entities\TeamInTournament;
use App\Entities\TeamSeasonRankInTournament;
use App\Repositories\TeamSeasonRankInTournamentRepository;

class TeamRankDisplay {
	/**
	 * @var array<TeamSeasonRankInTournament> $teamSeasonRanksInTournament
	 */
	private array $teamSeasonRanksInTournament;
	public function __construct(
		public TeamInTournament $teamInTournament,
		public bool $withLabel=false
	) {
		$teamSeasonRankInTournamentRepo = new TeamSeasonRankInTournamentRepository();
		$this->teamSeasonRanksInTournament = $teamSeasonRankInTournamentRepo->findAllByTeamAndTournament($this->teamInTournament->team, $this->teamInTournament->tournament);
	}

	public function render(): string {
		$withLabel = $this->withLabel;
		ob_start();
		foreach ($this->teamSeasonRanksInTournament as $teamSeasonRankInTournament) {
			if (!$teamSeasonRankInTournament->hasRank()) continue;
			$displayStyle = $teamSeasonRankInTournament->isSelectedByUser() ? '' : 'display:none';
			$classes = implode(' ', [$this->withLabel ? 'team-avg-rank' : 'rank', 'split_rank_element', 'ranked-split-'.$teamSeasonRankInTournament->rankedSplit->getName()]);
			$src = "/ddragon/img/ranks/mini-crests/{$teamSeasonRankInTournament->rank->getRankTierLowercase()}.svg";
			include BASE_PATH.'/resources/components/team/team-rank-display.php';
		}
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}
}