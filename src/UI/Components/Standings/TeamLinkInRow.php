<?php

namespace App\UI\Components\Standings;

use App\Domain\Entities\TeamInTournamentStage;
use App\UI\Components\Team\TeamRankDisplay;
use App\UI\Components\UI\PageLinkWrapper;

class TeamLinkInRow {
	public string $href;
	public string $teamNameTargetHtml;
	public string $teamLogoHtml;
	public string $ranksHtml = "";

	public function __construct(
		public TeamInTournamentStage $teamInTournamentStage
	) {
		$this->href = "/turnier/{$teamInTournamentStage->tournamentStage->rootTournament->id}/team/{$teamInTournamentStage->team->id}";

		$this->teamNameTargetHtml = PageLinkWrapper::makeTarget(
			"<span class='team-name' title='{$teamInTournamentStage->teamInRootTournament->nameInTournament}'>{$teamInTournamentStage->teamInRootTournament->nameInTournament}</span>"
		);

		$logoSrc = $this->teamInTournamentStage->teamInRootTournament->getLogoUrl();
		if (!$logoSrc) {
			$logoSrc = "data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs%3D";
		}
		$this->teamLogoHtml = "<img class='color-switch' src='$logoSrc' alt='Teamlogo'>";

		$this->ranksHtml = new TeamRankDisplay($this->teamInTournamentStage->teamInRootTournament);
	}

	public function render(): string {
		ob_start();
		include __DIR__.'/team-link-in-row.template.php';
		return ob_get_clean();
	}

	public function __toString(): string {
		return $this->render();
	}
}