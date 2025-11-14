<?php

namespace App\UI\Components;

use App\Domain\Entities\Matchup;
use App\Domain\Entities\TeamInTournament;
use App\Domain\Entities\Tournament;
use App\Domain\Entities\UpdateHistory;
use App\Domain\Entities\UpdateJob;
use App\Domain\Enums\Jobs\UpdateJobAction;
use App\Domain\Enums\Jobs\UpdateJobContextType;
use App\Domain\Enums\Jobs\UpdateJobStatus;
use App\Domain\Enums\Jobs\UpdateJobType;
use App\Domain\Repositories\UpdateHistoryRepository;
use App\Domain\Repositories\UpdateJobRepository;
use App\UI\Page\AssetManager;

class UpdateButton {
	private UpdateHistory $updateHistory;
    private ?UpdateJob $updateJob = null;
	private string $htmlDataString = '';
	private string $htmlClassString = '';
	public function __construct(
		public Tournament|TeamInTournament|Matchup $entity
	) {
        AssetManager::addJsAsset("components/updateButton.js");
		$updateHistoryRepo = new UpdateHistoryRepository();
        $updateJobRepo = new UpdateJobRepository();
		if ($entity instanceof Tournament) {
			$this->htmlClassString = 'user_update_group';
			$this->htmlDataString = " data-type='group' data-group='$entity->id'";
            // Suche nach laufendem Update
            $this->updateJob = $updateJobRepo->findLatest(
                UpdateJobType::USER,
                UpdateJobAction::UPDATE_GROUP,
                UpdateJobStatus::RUNNING,
                UpdateJobContextType::GROUP,
                $entity->id
            );
            // Wenn keins läuft, suche nach erfolgreichen Update
            if ($this->updateJob === null) {
                $this->updateJob = $updateJobRepo->findLatest(
                    UpdateJobType::USER,
                    UpdateJobAction::UPDATE_GROUP,
                    UpdateJobStatus::SUCCESS,
                    UpdateJobContextType::GROUP,
                    $entity->id
                );
            }
		}
		if ($entity instanceof TeamInTournament) {
			$this->htmlClassString = 'user_update_team';
			$this->htmlDataString = "data-type='team' data-team='{$entity->team->id}' data-tournament='{$entity->tournament->id}'";
			$this->updateJob = $updateJobRepo->findLatest(
				UpdateJobType::USER,
				UpdateJobAction::UPDATE_TEAM,
				UpdateJobStatus::RUNNING,
				UpdateJobContextType::TEAM,
				contextId: $entity->team->id,
				tournamentId: $entity->tournament->id
			);
			if ($this->updateJob === null) {
				$this->updateJob = $updateJobRepo->findLatest(
					UpdateJobType::USER,
					UpdateJobAction::UPDATE_TEAM,
					UpdateJobStatus::SUCCESS,
					UpdateJobContextType::TEAM,
					contextId: $entity->team->id,
					tournamentId: $entity->tournament->id
				);
			}
		}
		if ($entity instanceof Matchup) {
			$this->htmlClassString = 'user_update_match';
			$this->htmlDataString = "data-type='match' data-match='$entity->id'";
			$this->updateHistory = $updateHistoryRepo->findByMatchup($entity);
		}
	}

	public function render(): string {
		$htmlDataString = $this->htmlDataString;
		$htmlClassString = $this->htmlClassString;
		$updatediff = $this->updateJob?->getLastUpdateString() ?? "unbekannt";
		ob_start();
		include __DIR__.'/update-button.template.php';
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}
}