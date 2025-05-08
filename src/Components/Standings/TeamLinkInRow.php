<?php

namespace App\Components\Standings;

use App\Components\UI\PageLinkWrapper;
use App\Entities\TeamInTournamentStage;
use App\Entities\TeamSeasonRankInTournament;

class TeamLinkInRow {
	public string $href;
	public string $teamNameTargetHtml;
	public string $teamLogoHtml;
	public string $ranksHtml = "";

	public function __construct(
		public TeamInTournamentStage $teamInTournamentStage,
		/** @var array<TeamSeasonRankInTournament> $teamSeasonRanksInTournament */
		public array $teamSeasonRanksInTournament
	) {
		$this->href = "/turnier/{$teamInTournamentStage->tournamentStage->rootTournament->id}/team/{$teamInTournamentStage->team->id}";

		$this->teamNameTargetHtml = PageLinkWrapper::makeTarget(
			"<span class='team-name' title='{$teamInTournamentStage->team->name}'>{$teamInTournamentStage->team->name}</span>"
		);

		if ($this->teamInTournamentStage->team->logoId != null && file_exists(BASE_PATH."/public".$this->teamInTournamentStage->team->getLogoUrlForColorMode())) {
			$src = $this->teamInTournamentStage->team->getLogoUrlForColorMode();
		} else {
			$src = "data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs%3D";
		}
		$this->teamLogoHtml = "<img class='color-switch' src='$src' alt='Teamlogo'>";

		foreach ($this->teamSeasonRanksInTournament as $teamSeasonRankInTournament) {
			if (!$teamSeasonRankInTournament->hasRank()) continue;
			$displayStyle = $teamSeasonRankInTournament->isSelectedByUser() ? "" : "display:none;";
			$classes = implode(' ', ['rank', 'split_rank_element', "ranked-split-".$teamSeasonRankInTournament->rankedSplit->getName()]);
			$src = "/ddragon/img/ranks/mini-crests/{$teamSeasonRankInTournament->rank->getRankTierLowercase()}.svg";

			$this->ranksHtml .= "<span class='{$classes}' style='$displayStyle'><img class='rank-emblem-mini' src='{$src}' alt='{$teamSeasonRankInTournament->rank->getRankTier()}'>{$teamSeasonRankInTournament->rank->getRank()}</span>";
		}
	}

	public function render(): string {
		ob_start();
		include BASE_PATH . '/resources/components/standings/team-link-in-row.php';
		return ob_get_clean();
	}

	public function __toString(): string {
		return $this->render();
	}
}