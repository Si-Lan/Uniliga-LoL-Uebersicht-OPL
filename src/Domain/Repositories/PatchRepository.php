<?php

namespace App\Domain\Repositories;

use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Entities\Patch;
use App\Domain\Enums\SaveResult;
use App\Domain\ValueObjects\RepositorySaveResult;

class PatchRepository extends AbstractRepository {
	use DataParsingHelpers;
	protected static array $ALL_DATA_KEYS = ["patch","data","champion_webp","item_webp","spell_webp","runes_webp"];
	protected static array $REQUIRED_DATA_KEYS = ["patch"];

	private function mapToEntity(array $data): Patch {
		$data = $this->normalizeData($data);
		return new Patch(
			patchNumber: (string) $data['patch'],
			data: $this->boolOrNull($data['data']),
			championWebp: $this->boolOrNull($data['champion_webp']),
			itemWebp: $this->boolOrNull($data['item_webp']),
			spellWebp: $this->boolOrNull($data['spell_webp']),
			runesWebp: $this->boolOrNull($data['runes_webp'])
		);
	}

	public function findByPatchNumber(string $patchNumber): ?Patch {
		$query = "SELECT * FROM local_patches WHERE patch = ?";
		$data = $this->dbcn->execute_query($query, [$patchNumber])->fetch_assoc();

		return $data ? $this->mapToEntity($data) : null;
	}
	public function patchExists(string $patchNumber): bool {
		$query = "SELECT * FROM local_patches WHERE patch = ?";
		$data = $this->dbcn->execute_query($query, [$patchNumber]);
		return $data->num_rows > 0;
	}

	/**
	 * @return array<Patch>
	 */
	public function findAll(): array {
		$data = $this->dbcn->execute_query("SELECT * FROM local_patches")->fetch_all(MYSQLI_ASSOC);
		usort($data, function($a, $b) {
			return version_compare($a['patch'], $b['patch']);
		});

		$patches = [];
		foreach ($data as $patch) {
			$patches[] = $this->mapToEntity($patch);
		}
		return $patches;
	}

	public function findLatestPatchWithAllData(): Patch {
		$patches = $this->dbcn->execute_query("SELECT * FROM local_patches WHERE data IS TRUE AND champion_webp IS TRUE AND item_webp IS TRUE AND runes_webp IS TRUE AND spell_webp IS TRUE")->fetch_all(MYSQLI_ASSOC);
		usort($patches, function($a, $b) {
			return version_compare($a['patch'], $b['patch']);
		});

		return $this->mapToEntity(end($patches));
	}

	public function findLatestPatchByPatchString(string $patch): Patch {
		$patches = $this->dbcn->execute_query("SELECT * FROM local_patches WHERE data IS TRUE AND champion_webp IS TRUE AND item_webp IS TRUE AND runes_webp IS TRUE AND spell_webp IS TRUE")->fetch_all(MYSQLI_ASSOC);

		usort($patches, function($a, $b) {
			return version_compare($a['patch'], $b['patch']);
		});

		$selectedPatch = end($patches); // wähle zuerst neuesten Patch (fallback)
		// durchlaufe Patches, alt->neu
		foreach ($patches as $localPatch) {
			if (self::patchNumberCompare($localPatch['patch'], $patch) == 0) {
				$selectedPatch = $localPatch; // Patch existiert lokal, wähle ihn aus
				break;
			}
			if (self::patchNumberCompare($localPatch['patch'], $patch) > 0) {
				$selectedPatch = $localPatch; // Patch existiert nicht direkt, nehme ersten, der neuer ist
				break;
			}
		}
		return $this->mapToEntity($selectedPatch);
	}

	private static function patchNumberCompare(string $patch1, string $patch2): int {
		[$aMajor, $aMinor] = explode('.', $patch1);
		[$bMajor, $bMinor] = explode('.', $patch2);

		if ((int)$aMajor !== (int)$bMajor) {
			return (int)$aMajor <=> (int)$bMajor;
		}
		return (int)$aMinor <=> (int)$bMinor;
	}

	private function mapEntityToData(Patch $patch): array {
		return [
			'patch' => $patch->patchNumber,
			'data' => $this->intOrNull($patch->data),
			'champion_webp' => $this->intOrNull($patch->championWebp),
			'item_webp' => $this->intOrNull($patch->itemWebp),
			'spell_webp' => $this->intOrNull($patch->spellWebp),
			'runes_webp' => $this->intOrNull($patch->runesWebp)
		];
	}

	private function insert(Patch $patch): void {
		$data = $this->mapEntityToData($patch);
		$columns = implode(",", array_keys($data));
		$placeholders = implode(",", array_fill(0, count($data), "?"));
		$values = array_values($data);
		/** @noinspection SqlInsertValues */
		$query = "INSERT INTO local_patches ($columns) VALUES ($placeholders)";
		$this->dbcn->execute_query($query, $values);
	}

	private function update(Patch $patch): RepositorySaveResult {
		$existingPatch = $this->findByPatchNumber($patch->patchNumber);
		$dataNew = $this->mapEntityToData($patch);
		$dataOld = $this->mapEntityToData($existingPatch);
		$dataChanged = array_diff_assoc($dataNew, $dataOld);
		$dataPrevious = array_diff_assoc($dataOld, $dataNew);

		if (count($dataChanged) == 0) {
			return new RepositorySaveResult(SaveResult::NOT_CHANGED);
		}

		$set = implode(",", array_map(fn($key) => "$key = ?", array_keys($dataChanged)));
		$values = array_values($dataChanged);

		$query = "UPDATE local_patches SET $set WHERE patch = ?";
		$this->dbcn->execute_query($query, [...$values, $patch->patchNumber]);
		return new RepositorySaveResult(SaveResult::UPDATED, $dataChanged, $dataPrevious);
	}

	public function save(Patch $patch): RepositorySaveResult {
		try {
			if ($this->patchExists($patch->patchNumber)) {
				$saveResult = $this->update($patch);
			} else {
				$this->insert($patch);
				$saveResult = new RepositorySaveResult(SaveResult::INSERTED);
			}
		} catch (\Throwable $e) {
			$this->logger->error("Fehler beim Speichern von Patches: " . $e->getMessage() . "\n" . $e->getTraceAsString());
			$saveResult = new RepositorySaveResult(SaveResult::FAILED);
		}
		$saveResult->entity = $this->findByPatchNumber($patch->patchNumber);
		return $saveResult;
	}

	public function delete(Patch $patch): bool {
		if (!$this->patchExists($patch->patchNumber)) {
			return false;
		}
		$query = "DELETE FROM local_patches WHERE patch = ?";
		return $this->dbcn->execute_query($query, [$patch->patchNumber]);
	}
}