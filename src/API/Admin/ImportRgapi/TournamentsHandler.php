<?php

namespace App\API\Admin\ImportRgapi;

use App\API\AbstractHandler;
use App\Domain\Enums\Jobs\UpdateJobAction;
use App\Domain\Enums\Jobs\UpdateJobContextType;
use App\Domain\Enums\Jobs\UpdateJobType;
use App\Domain\Repositories\UpdateJobRepository;

class TournamentsHandler extends AbstractHandler {
	private UpdateJobRepository $updateJobRepo;
	public function __construct() {
		$this->updateJobRepo = new UpdateJobRepository();
	}


	public function postTournamentsPlayersPuuids(int $tournamentId): void {
		$this->checkRequestMethod('POST');
		$withoutPuuidOnly = isset($_GET["withoutPuuid"]) && $_GET["withoutPuuid"] === "true";

		$job = $this->updateJobRepo->createJob(UpdateJobType::ADMIN, UpdateJobAction::UPDATE_PUUIDS, UpdateJobContextType::TOURNAMENT, $tournamentId);

		$commandOptions = ["-j $job->id"];
		if ($withoutPuuidOnly) $commandOptions[] = "--without-puuid";

		$optionString = implode(" ", $commandOptions);
		exec("php ".BASE_PATH."/bin/admin_updates/update_puuids.php $optionString > /dev/null 2>&1 &");

		echo json_encode(['job_id'=>$job->id]);
	}
}