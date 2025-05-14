<?php

namespace App\Components;

use App\Entities\Matchup;
use App\Entities\TeamInTournament;
use App\Entities\Tournament;
use App\Entities\UpdateHistory;
use App\Repositories\TeamInTournamentStageRepository;
use App\Repositories\UpdateHistoryRepository;

class UpdateButton {
	private UpdateHistory $updateHistory;
	private string $htmlDataString = '';
	private string $htmlClassString = '';
	public function __construct(
		public Tournament|TeamInTournament|Matchup $entity
	) {
		$updateHistoryRepo = new UpdateHistoryRepository();
		if ($entity instanceof Tournament) {
			$this->htmlClassString = 'user_update_group';
			$this->htmlDataString = "data-group='$entity->id'";
			$this->updateHistory = $updateHistoryRepo->findByTournamentStage($entity);
		}
		if ($entity instanceof TeamInTournament) {
			$this->htmlClassString = 'user_update_team';
			$this->htmlDataString = "data-team='{$entity->team->id}' data-tournament='{$entity->tournament->id}'";
			$this->updateHistory = $updateHistoryRepo->findByTeamInTournament($entity);
		}
		if ($entity instanceof Matchup) {
			$this->htmlClassString = 'user_update_match';
			$this->htmlDataString = "data-match='$entity->id'";
			$this->updateHistory = $updateHistoryRepo->findByMatchup($entity);
		}
	}

	public function render(): string {
		$htmlDataString = $this->htmlDataString;
		$htmlClassString = $this->htmlClassString;
		$updatediff = $this->updateHistory->getLastUpdateString();
		ob_start();
		include BASE_PATH.'/resources/components/update-button.php';
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}
}