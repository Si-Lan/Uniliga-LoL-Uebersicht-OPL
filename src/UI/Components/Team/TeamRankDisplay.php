<?php

namespace App\UI\Components\Team;

use App\Domain\Entities\Team;
use App\Domain\Entities\TeamInTournament;
use App\Domain\Entities\TeamSeasonRankInTournament;
use App\Domain\Repositories\TeamSeasonRankInTournamentRepository;

class TeamRankDisplay {
	/**
	 * @var array<TeamSeasonRankInTournament>|array<Team> $teamRanks
	 */
	private array $teamRanks;
	public function __construct(
		public TeamInTournament|Team $team,
		public bool $withLabel=false
	) {
		if ($team instanceof TeamInTournament) {
			$teamSeasonRankInTournamentRepo = new TeamSeasonRankInTournamentRepository();
			$this->teamRanks = $teamSeasonRankInTournamentRepo->findAllByTeamAndTournament($this->team->team, $this->team->tournament);
		}
		if ($team instanceof Team) {
			$this->teamRanks = [$team];
		}
	}

	public function render(): string {
		$withLabel = $this->withLabel;
		ob_start();
		foreach ($this->teamRanks as $teamRank) {
			if (!$teamRank->hasRank()) continue;
			$displayStyle = ($teamRank instanceof Team || $teamRank->isSelectedByUser()) ? '' : 'display:none';
			$classes = implode(' ', array_filter([$this->withLabel ? 'team-avg-rank' : 'rank', 'split_rank_element', $teamRank instanceof TeamSeasonRankInTournament ? 'ranked-split-'.$teamRank->rankedSplit->getName() : '']));
			$src = "/ddragon/img/ranks/mini-crests/{$teamRank->rank->getRankTierLowercase()}.svg";
			include __DIR__.'/team-rank-display.template.php';
		}
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}
}