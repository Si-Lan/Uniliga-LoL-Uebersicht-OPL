<?php

namespace App\API\Admin;

use App\API\AbstractHandler;
use App\Service\Updater\PatchUpdater;

class PatchesHandler extends AbstractHandler {
	private PatchUpdater $patchUpdater;
	public function __construct() {
		$this->patchUpdater = new PatchUpdater();
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
}