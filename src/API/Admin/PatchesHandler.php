<?php

namespace App\API\Admin;

use App\API\AbstractHandler;
use App\Domain\Repositories\PatchRepository;
use App\Service\Updater\PatchUpdater;

class PatchesHandler extends AbstractHandler {
	private PatchUpdater $patchUpdater;
	private PatchRepository $patchRepo;
	public function __construct() {
		$this->patchUpdater = new PatchUpdater();
		$this->patchRepo = new PatchRepository();
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
	public function postPatchesJson(string $major, string $minor, string $patch) {
		$patchNumber = "$major.$minor.$patch";
		$patchEntity = $this->patchRepo->findByPatchNumber($patchNumber);
		if ($patchEntity === null) {
			$this->sendErrorResponse(404, "Patch not found");
		}

		echo $this->patchUpdater->downloadJsons($patchNumber, true);
	}
}