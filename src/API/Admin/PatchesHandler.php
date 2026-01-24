<?php

namespace App\API\Admin;

use App\API\AbstractHandler;
use App\Domain\Entities\Patch;
use App\Domain\Entities\UpdateJob;
use App\Domain\Enums\Jobs\UpdateJobAction;
use App\Domain\Enums\Jobs\UpdateJobContextType;
use App\Domain\Enums\Jobs\UpdateJobStatus;
use App\Domain\Enums\Jobs\UpdateJobType;
use App\Domain\Repositories\PatchRepository;
use App\Domain\Repositories\UpdateJobRepository;
use App\Service\Updater\PatchUpdater;

class PatchesHandler extends AbstractHandler {
	private PatchUpdater $patchUpdater;
	private PatchRepository $patchRepo;
	private UpdateJobRepository $jobRepo;
	public function __construct() {
		$this->patchUpdater = new PatchUpdater();
		$this->patchRepo = new PatchRepository();
		$this->jobRepo = new UpdateJobRepository();
	}

	private function combinePatchNumbers(string $major, string $minor, string $patch): string {
		return "$major.$minor.$patch";
	}
	private function validateAndGetPatchEntity(string $patchNumber): Patch {
		$patchEntity = $this->patchRepo->findByPatchNumber($patchNumber);
		if ($patchEntity === null) {
			$this->sendErrorResponse(404, "Patch not found");
		}
		return $patchEntity;
	}

	public function getPatches(string $major, string $minor, string $patch): void {
		$patchNumber = "$major.$minor.$patch";
		$patchEntity = $this->patchRepo->findByPatchNumber($patchNumber);
		if ($patchEntity === null) {
			$this->sendErrorResponse(404, "Patch not found");
		}
		echo json_encode($patchEntity);
	}

	public function postPatches(): void {
		$this->checkRequestMethod('POST');
		$requestBody = $this->parseRequestData();
		if (!array_key_exists('patch', $requestBody)) {
			$this->sendErrorResponse(400, 'Missing patch data');
		}
		echo $this->patchUpdater->addNewPatch($requestBody['patch']);
	}
	public function deletePatches(string $major, string $minor, string $patch): void {
		$this->checkRequestMethod('DELETE');
		$patchNumber = "$major.$minor.$patch";
		echo $this->patchUpdater->deletePatch($patchNumber);
	}
	public function postPatchesJson(string $major, string $minor, string $patch): void {
		$patchNumber = "$major.$minor.$patch";
		$patchEntity = $this->patchRepo->findByPatchNumber($patchNumber);
		if ($patchEntity === null) {
			$this->sendErrorResponse(404, "Patch not found");
		}

		echo $this->patchUpdater->downloadJsons($patchNumber, true);
	}

	public function postPatchesImgs(string $major, string $minor, string $patch): void {
		$patchNumber = $this->combinePatchNumbers($major, $minor, $patch);
		$patchEntity = $this->validateAndGetPatchEntity($patchNumber);

		$jobActions = [
			UpdateJobAction::DOWNLOAD_CHAMPION_IMAGES,
			UpdateJobAction::DOWNLOAD_ITEM_IMAGES,
			UpdateJobAction::DOWNLOAD_SPELL_IMAGES,
			UpdateJobAction::DOWNLOAD_RUNE_IMAGES
		];

		$jobIds = [];
		foreach ($jobActions as $jobAction) {
			$job = $this->startDownloadJob($patchNumber, $jobAction);
			$jobIds[] = $job->id;
		}

		echo json_encode(['job_ids' => $jobIds]);
	}

	public function postPatchesImgsChampions(string $major, string $minor, string $patch): void {
		$patchNumber = $this->combinePatchNumbers($major, $minor, $patch);
		$patchEntity = $this->validateAndGetPatchEntity($patchNumber);

		$job = $this->startDownloadJob($patchNumber, UpdateJobAction::DOWNLOAD_CHAMPION_IMAGES);

		echo json_encode(['job_id'=>$job->id]);
	}

	public function postPatchesImgsItems(string $major, string $minor, string $patch): void {
		$patchNumber = $this->combinePatchNumbers($major, $minor, $patch);
		$patchEntity = $this->validateAndGetPatchEntity($patchNumber);

		$job = $this->startDownloadJob($patchNumber, UpdateJobAction::DOWNLOAD_ITEM_IMAGES);

		echo json_encode(['job_id'=>$job->id]);
	}

	public function postPatchesImgsSummoners(string $major, string $minor, string $patch): void {
		$patchNumber = $this->combinePatchNumbers($major, $minor, $patch);
		$patchEntity = $this->validateAndGetPatchEntity($patchNumber);

		$job = $this->startDownloadJob($patchNumber, UpdateJobAction::DOWNLOAD_SPELL_IMAGES);

		echo json_encode(['job_id'=>$job->id]);
	}

	public function postPatchesImgsRunes(string $major, string $minor, string $patch): void {
		$patchNumber = $this->combinePatchNumbers($major, $minor, $patch);
		$patchEntity = $this->validateAndGetPatchEntity($patchNumber);

		$job = $this->startDownloadJob($patchNumber, UpdateJobAction::DOWNLOAD_RUNE_IMAGES);

		echo json_encode(['job_id'=>$job->id]);
	}

	private function startDownloadJob(string $patchNumber, UpdateJobAction $action): UpdateJob {
		$possibleRunningJob = $this->jobRepo->findLatest(
			UpdateJobType::ADMIN,
			$action,
			UpdateJobStatus::RUNNING,
			UpdateJobContextType::PATCH,
			contextName: $patchNumber
		);
		if ($possibleRunningJob !== null) {
			return $possibleRunningJob;
		}
		$job = $this->jobRepo->createJob(
			UpdateJobType::ADMIN,
			$action,
			UpdateJobContextType::PATCH,
			contextName: $patchNumber,
		);

		$overwrite = isset($_GET['overwrite']) && $_GET['overwrite'] === 'true';
		$optionsString = "-j $job->id";
		if ($overwrite) {
			$optionsString .= " --overwrite";
		}

		exec("php ".BASE_PATH."/bin/ddragon_updates/download_patch_imgs.php $optionsString > /dev/null 2>&1 &");

		return $job;
	}

	public function getPatchesSyncAll(): void {
		echo json_encode($this->patchUpdater->syncPatchDirsToDb());
	}

	public function getPatchesCheck(string $major, string $minor, string $patch): void {
		$patchNumber = $this->combinePatchNumbers($major, $minor, $patch);
		$patchEntity = $this->validateAndGetPatchEntity($patchNumber);

		$saveResult = $this->patchUpdater->checkExistingFilesForPatch($patchEntity);
		echo json_encode($saveResult);
	}
}