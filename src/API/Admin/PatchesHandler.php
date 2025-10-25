<?php

namespace App\API\Admin;

use App\API\AbstractHandler;
use App\Domain\Entities\Patch;
use App\Domain\Repositories\PatchRepository;
use App\Service\Updater\PatchUpdater;

class PatchesHandler extends AbstractHandler {
	private PatchUpdater $patchUpdater;
	private PatchRepository $patchRepo;
	public function __construct() {
		$this->patchUpdater = new PatchUpdater();
		$this->patchRepo = new PatchRepository();
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

	public function getPatchesChampionsCount(string $major, string $minor, string $patch): void {
		$patchNumber = $this->combinePatchNumbers($major, $minor, $patch);
		$patchEntity = $this->validateAndGetPatchEntity($patchNumber);

		echo $this->patchUpdater->imgCountFromJson($patchNumber, "champions");
	}
	public function getPatchesItemsCount(string $major, string $minor, string $patch): void {
		$patchNumber = $this->combinePatchNumbers($major, $minor, $patch);
		$patchEntity = $this->validateAndGetPatchEntity($patchNumber);

		echo $this->patchUpdater->imgCountFromJson($patchNumber, "items");
	}

	public function postPatchesImgsChampions(string $major, string $minor, string $patch): void {
		$patchNumber = $this->combinePatchNumbers($major, $minor, $patch);
		$patchEntity = $this->validateAndGetPatchEntity($patchNumber);

		$startIndex = isset($_GET['start']) ? (int)$_GET['start'] : null;
		$endIndex = isset($_GET['end']) ? (int)$_GET['end'] : null;
		if ($startIndex !== null && $endIndex !== null && ($startIndex < 0 || $endIndex <= $startIndex)) {
			$this->sendErrorResponse(400, "Invalid indices for champion batch");
		}

		$overwrite = isset($_GET['overwrite']) && $_GET['overwrite'] === 'true';

		$downloads = $this->patchUpdater->downloadChampionImgs($patchNumber, $overwrite, $startIndex, $endIndex);
		echo json_encode($downloads);
	}

	public function postPatchesImgsItems(string $major, string $minor, string $patch): void {
		$patchNumber = $this->combinePatchNumbers($major, $minor, $patch);
		$patchEntity = $this->validateAndGetPatchEntity($patchNumber);

		$startIndex = isset($_GET['start']) ? (int)$_GET['start'] : null;
		$endIndex = isset($_GET['end']) ? (int)$_GET['end'] : null;
		if ($startIndex !== null && $endIndex !== null && ($startIndex < 0 || $endIndex <= $startIndex)) {
			$this->sendErrorResponse(400, "Invalid indices for item batch");
		}

		$overwrite = isset($_GET['overwrite']) && $_GET['overwrite'] === 'true';

		$downloads = $this->patchUpdater->downloadItemImgs($patchNumber, $overwrite, $startIndex, $endIndex);
		echo json_encode($downloads);
	}

	public function postPatchesImgsSummoners(string $major, string $minor, string $patch): void {
		$patchNumber = $this->combinePatchNumbers($major, $minor, $patch);
		$patchEntity = $this->validateAndGetPatchEntity($patchNumber);

		$overwrite = isset($_GET['overwrite']) && $_GET['overwrite'] === 'true';

		$downloads = $this->patchUpdater->downloadSummonerImgs($patchNumber, $overwrite);
		echo json_encode($downloads);
	}

	public function postPatchesImgsRunes(string $major, string $minor, string $patch): void {
		$patchNumber = $this->combinePatchNumbers($major, $minor, $patch);
		$patchEntity = $this->validateAndGetPatchEntity($patchNumber);

		$overwrite = isset($_GET['overwrite']) && $_GET['overwrite'] === 'true';

		$downloads = $this->patchUpdater->downloadRuneImgs($patchNumber, $overwrite);
		echo json_encode($downloads);
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