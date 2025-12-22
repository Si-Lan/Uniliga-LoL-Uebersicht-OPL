<?php

namespace App\UI\Components;

use App\Core\Utilities\DateTimeHelper;
use App\Domain\Entities\Matchup;
use App\Domain\Entities\Team;
use App\Domain\Entities\TeamInTournament;
use App\Domain\Entities\Tournament;
use App\Domain\Entities\UpdateJob;
use App\Domain\Enums\Jobs\UpdateJobAction;
use App\Domain\Enums\Jobs\UpdateJobContextType;
use App\Domain\Enums\Jobs\UpdateJobStatus;
use App\Domain\Enums\Jobs\UpdateJobType;
use App\Domain\Repositories\UpdateJobRepository;
use App\UI\Page\AssetManager;

class UpdateButton {
    private ?UpdateJob $updateJob = null;
	private string $updatediff = "";
	private string $htmlDataString = '';
	private string $htmlClassString = '';
	public function __construct(
		public Tournament|TeamInTournament|Matchup $entity,
		public ?Team $teamContext = null
	) {
		$tournament = null;
        AssetManager::addJsAsset("components/updateButton.js");
        $updateJobRepo = new UpdateJobRepository();
		if ($this->entity instanceof Tournament) {
			$this->htmlClassString = 'user_update_group';
			$this->htmlDataString = " data-type='group' data-group='{$this->entity->id}'";
            // Suche nach laufendem Update
            $this->updateJob = $updateJobRepo->findLatest(
                UpdateJobType::USER,
                UpdateJobAction::UPDATE_GROUP,
                UpdateJobStatus::RUNNING,
                UpdateJobContextType::GROUP,
				$this->entity->id
            );
            // Wenn keins läuft, suche nach erfolgreichen Update
            if ($this->updateJob === null) {
                $this->updateJob = $updateJobRepo->findLatest(
                    UpdateJobType::USER,
                    UpdateJobAction::UPDATE_GROUP,
                    UpdateJobStatus::SUCCESS,
                    UpdateJobContextType::GROUP,
					$this->entity->id
                );
            }
			$tournament = $this->entity->getRootTournament();
		}
		if ($this->entity instanceof TeamInTournament) {
			$this->htmlClassString = 'user_update_team';
			$this->htmlDataString = "data-type='team' data-team='{$this->entity->team->id}' data-tournament='{$this->entity->tournament->id}'";
			$this->updateJob = $updateJobRepo->findLatest(
				UpdateJobType::USER,
				UpdateJobAction::UPDATE_TEAM,
				UpdateJobStatus::RUNNING,
				UpdateJobContextType::TEAM,
				contextId: $this->entity->team->id,
				tournamentId: $this->entity->tournament->id
			);
			if ($this->updateJob === null) {
				$this->updateJob = $updateJobRepo->findLatest(
					UpdateJobType::USER,
					UpdateJobAction::UPDATE_TEAM,
					UpdateJobStatus::SUCCESS,
					UpdateJobContextType::TEAM,
					contextId: $this->entity->team->id,
					tournamentId: $this->entity->tournament->id
				);
			}
			$tournament = $this->entity->tournament;
		}
		if ($this->entity instanceof Matchup) {
			$this->htmlClassString = 'user_update_match';
			$dataTeam = $this->teamContext !== null ? " data-team='{$this->teamContext->id}'" : '';
			$this->htmlDataString = "data-type='match' data-match='{$this->entity->id}'$dataTeam";
			$this->updateJob = $updateJobRepo->findLatest(
				UpdateJobType::USER,
				UpdateJobAction::UPDATE_MATCH,
				UpdateJobStatus::RUNNING,
				UpdateJobContextType::MATCHUP,
				$this->entity->id
			);
			if ($this->updateJob === null) {
				$this->updateJob = $updateJobRepo->findLatest(
					UpdateJobType::USER,
					UpdateJobAction::UPDATE_MATCH,
					UpdateJobStatus::SUCCESS,
					UpdateJobContextType::MATCHUP,
					$this->entity->id
				);
			}
			$tournament = $this->entity->tournamentStage->getRootTournament();
		}

		if ($tournament?->lastCronUpdate > $this->updateJob?->getLastUpdateTime()) {
			$this->updatediff = DateTimeHelper::getRelativeTimeString($tournament?->lastCronUpdate);
		} else {
			$this->updatediff =  DateTimeHelper::getRelativeTimeString($this->updateJob?->getLastUpdateTime());
		}
	}

	public function render(): string {
		$htmlDataString = $this->htmlDataString;
		$htmlClassString = $this->htmlClassString;
		$updatediff = $this->updatediff;
		ob_start();
		include __DIR__.'/update-button.template.php';
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}
}