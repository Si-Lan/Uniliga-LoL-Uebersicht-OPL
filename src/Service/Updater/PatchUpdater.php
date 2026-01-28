<?php

namespace App\Service\Updater;

use App\Domain\Entities\Patch;
use App\Domain\Entities\UpdateJob;
use App\Domain\Repositories\PatchRepository;
use App\Domain\Repositories\UpdateJobRepository;
use App\Domain\ValueObjects\RepositorySaveResult;
use FilesystemIterator;

class PatchUpdater {
	private const string PATCH_JSON_URL = "https://ddragon.leagueoflegends.com/api/versions.json";
	private const string LOCAL_DDRAGON_DIR = BASE_PATH . "/public/assets/ddragon";
	private PatchRepository $patchRepo;
	private \DirectoryIterator $ddragonDirIterator;
	private UpdateJobRepository $updateJobRepo;
	public function __construct() {
		$this->patchRepo = new PatchRepository();
		$this->ddragonDirIterator = new \DirectoryIterator(self::LOCAL_DDRAGON_DIR);
		$this->updateJobRepo = new UpdateJobRepository();
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
			if ($fileinfo->isDir() && $fileinfo->getFilename() !== "img" && !$fileinfo->isDot()) {
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
			mkdir(self::LOCAL_DDRAGON_DIR . "/$patchNumber/data", 0777, true);
		}

		file_put_contents(self::LOCAL_DDRAGON_DIR . "/$patchNumber/data/champion.json", file_get_contents($this->getChampionsJsonUrl($patchNumber)));
		file_put_contents(self::LOCAL_DDRAGON_DIR . "/$patchNumber/data/item.json", file_get_contents($this->getItemsJsonUrl($patchNumber)));
		file_put_contents(self::LOCAL_DDRAGON_DIR . "/$patchNumber/data/summoner.json", file_get_contents($this->getSummonerSpellsJsonUrl($patchNumber)));
		file_put_contents(self::LOCAL_DDRAGON_DIR . "/$patchNumber/data/runesReforged.json", file_get_contents($this->getRunesJsonUrl($patchNumber)));

		$patch->data = true;
		$this->patchRepo->save($patch);
		return true;
	}

	/**
	 * Für Champions und Items liefert diese Funktion die jeweilige Anzahl aus der JSON-Datei des Patches.
	 * Damit diese client-seitig per JS in Batches geladen werden können
	 **/
	// (Für Summoner und Runen nicht notwendig, da diese nur in kleinen Mengen existieren)
	// (Außerdem lässt sich für Runen aufgrund der verschachtelten Struktur keine sinnvolle Zahl liefern)
	public function imgCountFromJson(string $patchNumber, string $type): int {
		$types = ["champions","items"];
		if (!in_array($type, $types)) {
			return 0;
		}

		switch ($type) {
			case "champions":
				if (!file_exists(self::LOCAL_DDRAGON_DIR . "/$patchNumber/data/champion.json")) {
					return 0;
				}
				$champions = json_decode(file_get_contents(self::LOCAL_DDRAGON_DIR . "/$patchNumber/data/champion.json"), true);
				return count($champions['data']);
			case "items":
				if (!file_exists(self::LOCAL_DDRAGON_DIR . "/$patchNumber/data/item.json")) {
					return 0;
				}
				$items = json_decode(file_get_contents(self::LOCAL_DDRAGON_DIR . "/$patchNumber/data/item.json"), true);
				return count($items['data']);
			default:
				return 0;
		}
	}

	/**
	 * @param string $patchNumber
	 * @param bool $overwrite
	 * @param UpdateJob|null $job
	 * @return bool|array<string> array of downloaded imgPaths or false, if no JSON-File for Patch exists
	 */
	public function downloadChampionImgs(string $patchNumber, bool $overwrite = false, ?UpdateJob $job = null): bool|array {
		$patch = $this->patchRepo->findByPatchNumber($patchNumber);
		$championJsonFile = self::LOCAL_DDRAGON_DIR . "/$patchNumber/data/champion.json";
		$ddragonSourceUrl = "https://ddragon.leagueoflegends.com/cdn/$patchNumber/img/champion/";
		$targetDir = self::LOCAL_DDRAGON_DIR . "/$patchNumber/img/champion";

		if ($patch === null || !file_exists($championJsonFile)) {
			return false;
		}
		$champions = json_decode(file_get_contents($championJsonFile), true)['data'];

		$downloads = [];
		$i = 1;
		foreach ($champions as $champion) {
			$downloads[$champion["id"]] = $this->downloadAndConvertImg($ddragonSourceUrl . $champion["image"]["full"], $targetDir, $champion["id"], $overwrite);
			if ($job !== null) {
				$job->progress = $i / count($champions) * 100;
				$this->updateJobRepo->save($job);
			}
			$i++;
		}

		$patch = $this->patchRepo->findByPatchNumber($patchNumber);
		$patch->championWebp = true;
		$this->patchRepo->save($patch);

		return $downloads;
	}

	/**
	 * @param string $patchNumber
	 * @param bool $overwrite
	 * @param UpdateJob|null $job
	 * @return bool|array<string> array of downloaded imgPaths or false, if no JSON-File for Patch exists
	 */
	public function downloadItemImgs(string $patchNumber, bool $overwrite = false, ?UpdateJob $job = null): bool|array {
		$patch = $this->patchRepo->findByPatchNumber($patchNumber);
		$itemJsonFile = self::LOCAL_DDRAGON_DIR . "/$patchNumber/data/item.json";
		$ddragonSourceUrl = "https://ddragon.leagueoflegends.com/cdn/$patchNumber/img/item/";
		$targetDir = self::LOCAL_DDRAGON_DIR . "/$patchNumber/img/item";

		if ($patch === null || !file_exists($itemJsonFile)) {
			return false;
		}
		$items = json_decode(file_get_contents($itemJsonFile), true)['data'];

		$downloads = [];
		$i = 1;
		foreach ($items as $item_id=>$item) {
			$downloads[$item_id] = $this->downloadAndConvertImg($ddragonSourceUrl . $item["image"]["full"], $targetDir, $item_id, $overwrite);
			if ($job !== null) {
				$job->progress = $i / count($items) * 100;
				$this->updateJobRepo->save($job);
			}
			$i++;
		}

		$patch = $this->patchRepo->findByPatchNumber($patchNumber);
		$patch->itemWebp = true;
		$this->patchRepo->save($patch);

		return $downloads;
	}

	/**
	 * @param string $patchNumber
	 * @param bool $overwrite
	 * @param UpdateJob|null $job
	 * @return bool|array<string> array of downloaded imgPaths or false, if no JSON-File for Patch exists
	 */
	public function downloadSummonerImgs(string $patchNumber, bool $overwrite = false, ?UpdateJob $job = null): bool|array {
		$patch = $this->patchRepo->findByPatchNumber($patchNumber);
		$summonerJsonFile = self::LOCAL_DDRAGON_DIR . "/$patchNumber/data/summoner.json";
		$ddragonSourceUrl = "https://ddragon.leagueoflegends.com/cdn/$patchNumber/img/spell/";
		$targetDir = self::LOCAL_DDRAGON_DIR . "/$patchNumber/img/spell";

		if ($patch === null || !file_exists($summonerJsonFile)) {
			return false;
		}
		$summoners = json_decode(file_get_contents($summonerJsonFile), true);

		$downloads = [];
		$i = 1;
		foreach ($summoners['data'] as $summoner_id=>$summoner) {
			$downloads[$summoner_id] = $this->downloadAndConvertImg($ddragonSourceUrl . $summoner['image']['full'], $targetDir, $summoner_id, $overwrite);
			if ($job !== null) {
				$job->progress = $i / count($summoners['data']) * 100;
				$this->updateJobRepo->save($job);
			}
			$i++;
		}

		$patch = $this->patchRepo->findByPatchNumber($patchNumber);
		$patch->spellWebp = true;
		$this->patchRepo->save($patch);

		return $downloads;
	}

	/**
	 * @param string $patchNumber
	 * @param bool $overwrite
	 * @param UpdateJob|null $job
	 * @return bool|array<string> array of downloaded imgPaths or false, if no JSON-File for Patch exists
	 */
	public function downloadRuneImgs(string $patchNumber, bool $overwrite = false, ?UpdateJob $job = null): bool|array {
		$patch = $this->patchRepo->findByPatchNumber($patchNumber);
		$runesJsonFile = self::LOCAL_DDRAGON_DIR . "/$patchNumber/data/runesReforged.json";
		$ddragonSourceUrl = "https://ddragon.leagueoflegends.com/cdn/img/";
		$targetDir = self::LOCAL_DDRAGON_DIR . "/$patchNumber/img/";

		if ($patch === null || !file_exists($runesJsonFile)) {
			return false;
		}
		$runes = json_decode(file_get_contents($runesJsonFile), true);

		$downloads = [];
		$i = 1;
		foreach ($runes as $runeTree) {
			$runeTreeSubdir = implode("/",explode("/",$runeTree["icon"],-1));
			$imageName = explode("/",$runeTree["icon"]);
			$imageName = explode(".", end($imageName))[0];
			$downloads[$imageName] = $this->downloadAndConvertImg($ddragonSourceUrl . $runeTree["icon"], $targetDir . $runeTreeSubdir, $imageName, $overwrite);

			foreach ($runeTree["slots"][0]["runes"] as $keystone) {
				$keystoneSubdir = implode("/",explode("/",$keystone["icon"],-1));
				$imageName = explode("/",$keystone["icon"]);
				$imageName = explode(".", end($imageName))[0];
				$downloads[$imageName] = $this->downloadAndConvertImg($ddragonSourceUrl . $keystone["icon"], $targetDir . $keystoneSubdir, $imageName, $overwrite);
			}

			if ($job !== null) {
				$job->progress = $i / count($runes) * 100;
				$this->updateJobRepo->save($job);
			}
			$i++;
		}

		$patch = $this->patchRepo->findByPatchNumber($patchNumber);
		$patch->runesWebp = true;
		$this->patchRepo->save($patch);
		return $downloads;
	}


	/**
	 * @param string $sourceUrl
	 * @param string $targetDir
	 * @param string $targetFilename
	 * @param bool $overwrite
	 * @return bool|string the path to the downloaded file or false if the file already exists and $overwrite is false
	 */
	private function downloadAndConvertImg(string $sourceUrl, string $targetDir, string $targetFilename, bool $overwrite): bool|string {
		if (!file_exists($targetDir)) {
			mkdir($targetDir, 0777, true);
		}
		$targetPath = realpath($targetDir)."/$targetFilename.webp";
		if (file_exists($targetPath) && !$overwrite) {
			return false;
		}

		$img = imagecreatefrompng($sourceUrl);
		imagepalettetotruecolor($img);
		imagealphablending($img, true);
		imagesavealpha($img, true);
		imagewebp($img, $targetPath, 50);
		imagedestroy($img);
		return $targetPath;
	}

	/**
	 * @return array{added: array<string>, patches: array<Patch>}
	 */
	public function syncPatchDirsToDb(): array {
		$directoryPatchNumbers = $this->getPatchNumbersFromDirectories();
		$dbPatches = $this->patchRepo->findAll();
		$databasePatchNumbers = array_map(fn($patch) => $patch->patchNumber, $dbPatches);
		foreach ($directoryPatchNumbers as $patchNumber) {
			if (!in_array($patchNumber, $databasePatchNumbers)) {
				$this->addNewPatch($patchNumber);
			}
		}

		return ["added" => array_values(array_diff($directoryPatchNumbers, $databasePatchNumbers)), "patches" => array_reverse($this->patchRepo->findAll())];
	}

	public function checkExistingFilesForPatch(Patch $patch): RepositorySaveResult {
		$patchDir = self::LOCAL_DDRAGON_DIR."/".$patch->patchNumber;
		$dataCheck = null;
		$championCheck = null;
		$itemCheck = null;
		$spellCheck = null;
		$runesCheck = null;
		if (!file_exists($patchDir."/data")) {
			$dataCheck = false;
		}
		if (!file_exists($patchDir."/img/champion")) {
			$championCheck = false;
		}
		if (!file_exists($patchDir."/img/item")) {
			$itemCheck = false;
		}
		if (!file_exists($patchDir."/img/spell")) {
			$spellCheck = false;
		}
		if (!file_exists($patchDir."/img/perk-images")) {
			$runesCheck = false;
		}

		if (is_null($dataCheck)) {
			if (file_exists($patchDir."/data/champion.json")
				&& file_exists($patchDir."/data/item.json")
				&& file_exists($patchDir."/data/summoner.json")
				&& file_exists($patchDir."/data/runesReforged.json")
			) {
				$dataCheck = true;
			} else {
				$dataCheck = false;
			}
		}

		if (is_null($championCheck) && $dataCheck) {
			$champions = json_decode(file_get_contents($patchDir . "/data/champion.json"), true)['data'];
			foreach ($champions as $champion) {
				if (!file_exists($patchDir . "/img/champion/" . $champion["id"] . ".webp")) {
					$championCheck = false;
					break;
				}
			}
			if (is_null($championCheck)) {
				$championCheck = true;
			}
		}

		if (is_null($itemCheck) && $dataCheck) {
			$items = json_decode(file_get_contents($patchDir . "/data/item.json"), true)['data'];
			foreach ($items as $item_id=>$item) {
				if (!file_exists($patchDir . "/img/item/" . $item_id . ".webp")) {
					$itemCheck = false;
					break;
				}
			}
			if (is_null($itemCheck)) {
				$itemCheck = true;
			}
		}

		if (is_null($spellCheck) && $dataCheck) {
			$summoners = json_decode(file_get_contents($patchDir . "/data/summoner.json"), true)['data'];
			foreach ($summoners as $summoner_id=>$summoner) {
				if (!file_exists($patchDir . "/img/spell/" . $summoner_id . ".webp")) {
					$spellCheck = false;
					break;
				}
			}
			if (is_null($spellCheck)) {
				$spellCheck = true;
			}
		}

		if (is_null($runesCheck) && $dataCheck) {
			$runes = json_decode(file_get_contents($patchDir . "/data/runesReforged.json"), true);
			foreach ($runes as $runeTree) {
				$treeIcon = explode(".",$runeTree["icon"])[0].".webp";
				if (!file_exists($patchDir . "/img/" . $treeIcon)) {
					$runesCheck = false;
					break;
				}
				foreach ($runeTree["slots"][0]["runes"] as $keystone) {
					$runeIcon = explode(".",$keystone["icon"])[0].".webp";
					if (!file_exists($patchDir . "/img/" . $runeIcon)) {
						$runesCheck = false;
						break;
					}
				}
			}
			if (is_null($runesCheck)) {
				$runesCheck = true;
			}
		}


		$patch->data = $dataCheck;
		$patch->championWebp = $championCheck;
		$patch->itemWebp = $itemCheck;
		$patch->spellWebp = $spellCheck;
		$patch->runesWebp = $runesCheck;
		return $this->patchRepo->save($patch);
	}
}