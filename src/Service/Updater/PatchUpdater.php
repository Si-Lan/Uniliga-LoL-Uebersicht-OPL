<?php

namespace App\Service\Updater;

use App\Domain\Entities\Patch;
use App\Domain\Repositories\PatchRepository;
use FilesystemIterator;

class PatchUpdater {
	private const string PATCH_JSON_URL = "https://ddragon.leagueoflegends.com/api/versions.json";
	private const string LOCAL_DDRAGON_DIR = BASE_PATH . "/public/assets/ddragon";
	private PatchRepository $patchRepo;
	private \DirectoryIterator $ddragonDirIterator;
	public function __construct() {
		$this->patchRepo = new PatchRepository();
		$this->ddragonDirIterator = new \DirectoryIterator(self::LOCAL_DDRAGON_DIR);
	}

	private function getChampionsJsonUrl(string $patchNumber): string {
		return "https://ddragon.leagueoflegends.com/cdn/$patchNumber/data/en_US/champion.json";
	}
	private function getItemsJsonUrl(string $patchNumber): string {
		return "https://ddragon.leagueoflegends.com/cdn/$patchNumber/data/en_US/item.json";
	}
	private function getSummonerSpellsJsonUrl(string $patchNumber): string {
		return "https://ddragon.leagueoflegends.com/cdn/$patchNumber/data/en_US/summoner.json";
	}
	private function getRunesJsonUrl(string $patchNumber): string {
		return "https://ddragon.leagueoflegends.com/cdn/$patchNumber/data/en_US/runesReforged.json";
	}

	/**
	 * @throws \Exception
	 */
	public function getPatchNumbersExternal(): array {
		$response = file_get_contents(self::PATCH_JSON_URL);
		if (!isset($http_response_header)) {
			throw new \Exception('No response from DDragon API');
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

	public function getPatchNumbersFromDirectories(): array {
		$patchNumbers = [];
		foreach ($this->ddragonDirIterator as $fileinfo) {
			if ($fileinfo->isDir() && $fileinfo->getFilename() !== "img") {
				$patchNumbers[] = $fileinfo->getFilename();
			}
		}
		usort($patchNumbers, "version_compare");
		return array_reverse($patchNumbers);
	}

	public function addNewPatch(string $patchNumber): bool {
		if (!file_exists(self::LOCAL_DDRAGON_DIR . "/$patchNumber")) {
			mkdir(self::LOCAL_DDRAGON_DIR . "/$patchNumber");
		}
		if ($this->patchRepo->patchExists($patchNumber)) {
			return false;
		}
		$patch = new Patch($patchNumber);
		$this->patchRepo->save($patch);
		return true;
	}
	public function deletePatch(string $patchNumber): bool {
		if (file_exists(self::LOCAL_DDRAGON_DIR . "/$patchNumber")) {
			$this->deletePatchFiles($patchNumber);
		}
		$patch = $this->patchRepo->findByPatchNumber($patchNumber);
		if ($patch === null) {
			return false;
		}
		$this->patchRepo->delete($patch);
		return true;
	}
	private function deletePatchFiles(string $patchNumber): bool {
		$patchDir = self::LOCAL_DDRAGON_DIR . "/$patchNumber";
		$it = new \RecursiveDirectoryIterator($patchDir, FilesystemIterator::SKIP_DOTS);
		$files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
		foreach ($files as $file) {
			if ($file->isDir()) {
				rmdir($file->getRealPath());
			} else {
				unlink($file->getRealPath());
			}
		}
		return rmdir($patchDir);
	}

	public function downloadJsons(string $patchNumber, bool $force = false): bool {
		$patch = $this->patchRepo->findByPatchNumber($patchNumber);
		if ($patch === null || ($patch->data === true && !$force)) {
			return false;
		}
		if (!file_exists(self::LOCAL_DDRAGON_DIR . "/$patchNumber/data")) {
			mkdir(self::LOCAL_DDRAGON_DIR . "/$patchNumber/data");
		}

		file_put_contents(self::LOCAL_DDRAGON_DIR . "/$patchNumber/data/champion.json", file_get_contents($this->getChampionsJsonUrl($patchNumber)));
		file_put_contents(self::LOCAL_DDRAGON_DIR . "/$patchNumber/data/item.json", file_get_contents($this->getItemsJsonUrl($patchNumber)));
		file_put_contents(self::LOCAL_DDRAGON_DIR . "/$patchNumber/data/summoner.json", file_get_contents($this->getSummonerSpellsJsonUrl($patchNumber)));
		file_put_contents(self::LOCAL_DDRAGON_DIR . "/$patchNumber/data/runesReforged.json", file_get_contents($this->getRunesJsonUrl($patchNumber)));

		$patch->data = true;
		$this->patchRepo->save($patch);
		return true;
	}
}