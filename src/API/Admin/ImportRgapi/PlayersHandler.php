<?php

namespace App\API\Admin\ImportRgapi;

use App\API\AbstractHandler;
use App\Domain\Enums\Jobs\UpdateJobAction;
use App\Domain\Enums\Jobs\UpdateJobType;
use App\Domain\Repositories\UpdateJobRepository;
use App\Service\JobLauncher;
use App\Service\Updater\PlayerUpdater;

class PlayersHandler extends AbstractHandler {
	private PlayerUpdater $playerUpdater;
	private UpdateJobRepository $updateJobRepo;
	public function __construct() {
		$this->playerUpdater = new PlayerUpdater();
		$this->updateJobRepo = new UpdateJobRepository();
	}

	public function postPlayersPuuid(int $playerId): void {
		$this->checkRequestMethod('POST');

		try {
			$saveResult = $this->playerUpdater->updatePuuidByRiotId($playerId);
		} catch (\Exception $e) {
			$this->sendErrorResponse($e->getCode(), $e->getMessage());
		}

		echo json_encode($saveResult);
	}
	public function postPlayersAllPuuid(): void {
		$this->checkRequestMethod('POST');
        $withoutPuuidOnly = isset($_GET["withoutPuuid"]) && $_GET["withoutPuuid"] === "true";

		$job = $this->updateJobRepo->createJob(
			UpdateJobType::ADMIN,
			UpdateJobAction::UPDATE_PUUIDS
		);

		$options = $withoutPuuidOnly ? " --without-puuid" : "";
		JobLauncher::launch($job, $options);

		echo json_encode(['job_id' => $job->id]);
	}

    public function postPlayersRiotid(int $playerId): void {
        $this->checkRequestMethod('POST');

        try {
            $saveResult = $this->playerUpdater->updateRiotIdByPuuid($playerId);
        } catch (\Exception $e) {
            $this->sendErrorResponse($e->getCode(), $e->getMessage());
        }

        echo json_encode($saveResult);
    }

    public function postPlayersRank(int $playerId): void {
        $this->checkRequestMethod('POST');

        try {
            $saveResult = $this->playerUpdater->updateRank($playerId);
        } catch (\Exception $e) {
            $this->sendErrorResponse($e->getCode(), $e->getMessage());
        }

        echo json_encode($saveResult);
    }

	public function postPlayersAllRank(): void {
		$this->checkRequestMethod('POST');

		$job = $this->updateJobRepo->createJob(
			UpdateJobType::ADMIN,
			UpdateJobAction::UPDATE_PLAYER_RANKS
		);

		JobLauncher::launch($job);

		echo json_encode(['job_id' => $job->id]);
	}
}