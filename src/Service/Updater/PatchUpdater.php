<?php

namespace App\Service\Updater;

use App\Domain\Repositories\PatchRepository;

class PatchUpdater {
	private const string PATCH_JSON_URL = "https://ddragon.leagueoflegends.com/api/versions.json";
	private PatchRepository $patchRepo;
	public function __construct() {
		$this->patchRepo = new PatchRepository();
	}

	/**
	 * @throws \Exception
	 */
	public function getPatchNumbersExternal(): array {
		$response = file_get_contents(self::PATCH_JSON_URL);
		if (!isset($http_response_header)) {
			throw new \Exception('No response from DDragon API');;
		}
		$httpStatus = $http_response_header[0] ?? '';
		if ($response === false || !str_contains($httpStatus, '200')) {
			throw new \Exception("Error from DDragon API: $httpStatus");
		}

		$patchNumbers = json_decode($response);

		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new \Exception('Invalid JSON received from DDragon API');
		}

		return $patchNumbers;
	}

	/**
	 * @throws \Exception
	 */
	public function getNewPatchNumbersExternal(): array {
		$externalPatches = $this->getPatchNumbersExternal();
		$localPatches = $this->patchRepo->findAll();
		if (empty($localPatches)) {
			return array_slice($externalPatches, 0, 15);
		}

		$latestLocalPatch = end($localPatches);
		$latestPatchIndexInExternalPatches = array_search($latestLocalPatch->patchNumber, $externalPatches);

		return array_slice($externalPatches, 0, $latestPatchIndexInExternalPatches);
	}

	/**
	 * @throws \Exception
	 */
	public function getOldPatchNumbersExternal(int $limit = 15): array {
		$externalPatches = $this->getPatchNumbersExternal();
		$localPatches = $this->patchRepo->findAll();
		if (empty($localPatches)) {
			return array_slice($externalPatches, 0, 15);
		}

		$oldestLocalPatch = $localPatches[0];
		$oldestPatchIndexInExternalPatches = array_search($oldestLocalPatch->patchNumber, $externalPatches);

		return array_slice($externalPatches, $oldestPatchIndexInExternalPatches+1, $limit);
	}

	/**
	 * @throws \Exception
	 */
	public function getIntermediatePatchNumbersExternal(): array {
		$externalPatches = $this->getPatchNumbersExternal();
		$localPatches = $this->patchRepo->findAll();
		if (empty($localPatches)) {
			return [];
		}

		$latestLocalPatch = end($localPatches);
		$oldestLocalPatch = $localPatches[0];

		$latestPatchIndexInExternalPatches = array_search($latestLocalPatch->patchNumber, $externalPatches);
		$oldestPatchIndexInExternalPatches = array_search($oldestLocalPatch->patchNumber, $externalPatches);

		$startIndex = min($latestPatchIndexInExternalPatches, $oldestPatchIndexInExternalPatches);
		$endIndex = max($latestPatchIndexInExternalPatches, $oldestPatchIndexInExternalPatches);

		return array_slice($externalPatches, $startIndex, $endIndex - $startIndex + 1);
	}


}