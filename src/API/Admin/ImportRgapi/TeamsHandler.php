<?php

namespace App\API\Admin\ImportRgapi;

use App\API\AbstractHandler;
use App\Domain\Enums\Jobs\UpdateJobAction;
use App\Domain\Enums\Jobs\UpdateJobType;
use App\Domain\Repositories\UpdateJobRepository;
use App\Service\Updater\TeamUpdater;

class TeamsHandler extends AbstractHandler {
    private TeamUpdater $teamUpdater;
	private UpdateJobRepository $updateJobRepo;
    public function __construct() {
        $this->teamUpdater = new TeamUpdater();
		$this->updateJobRepo = new UpdateJobRepository();
    }

    public function postTeamsRank(int $teamId): void {
        $this->checkRequestMethod('POST');

        try {
            $saveResult = $this->teamUpdater->updateRank($teamId);
        } catch (\Exception $e) {
            $this->sendErrorResponse($e->getCode(), $e->getMessage());
        }

        echo json_encode($saveResult);
    }
	public function postTeamsAllRank(): void {
		$this->checkRequestMethod('POST');

		$job = $this->updateJobRepo->createJob(
			UpdateJobType::ADMIN,
			UpdateJobAction::UPDATE_TEAM_RANKS
		);

		$optionsString = "-j $job->id";
		exec("php ".BASE_PATH."/bin/admin_updates/update_team_ranks.php $optionsString > /dev/null 2>&1 &");

		echo json_encode(['job_id' => $job->id]);
	}
}