<?php

namespace App\UI\Components;

use App\Domain\Entities\Matchup;
use App\Domain\Entities\TeamInTournament;
use App\Domain\Entities\Tournament;
use App\Domain\Entities\UpdateHistory;
use App\Domain\Repositories\UpdateHistoryRepository;

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
		include __DIR__.'/update-button.template.php';
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}
}