<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\Patch;

class PatchRepository extends AbstractRepository {
	protected static array $ALL_DATA_KEYS = ["patch","data","champion_webp","item_webp","spell_webp","runes_webp"];
	protected static array $REQUIRED_DATA_KEYS = ["patch"];

	private function mapToEntity(array $data): Patch {
		$data = $this->normalizeData($data);
		return new Patch(
			patchNumber: (string) $data['patch'],
			data: (bool) $data['data'] ?? false,
			championWebp: (bool) $data['champion_webp'] ?? false,
			itemWebp: (bool) $data['item_webp'] ?? false,
			spellWebp: (bool) $data['spell_webp'] ?? false,
			runesWebp: (bool) $data['runes_webp'] ?? false
		);
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
}