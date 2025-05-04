<?php

namespace App\Repositories;

use App\Database\DatabaseConnection;
use App\Entities\Patch;

class PatchRepository extends AbstractRepository {
	private \mysqli $dbcn;
	protected static array $ALL_DATA_KEYS = ["patch","data","champion_webp","item_webp","spell_webp","runes_webp"];
	protected static array $REQUIRED_DATA_KEYS = ["patch"];

	public function __construct() {
		$this->dbcn = DatabaseConnection::getConnection();
	}

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

	public function findLatestPatchWithAllData(): Patch {
		$patches = $this->dbcn->execute_query("SELECT * FROM local_patches WHERE data IS TRUE AND champion_webp IS TRUE AND item_webp IS TRUE AND runes_webp IS TRUE AND spell_webp IS TRUE")->fetch_all(MYSQLI_ASSOC);
		usort($patches, function($a, $b) {
			return version_compare($a['patch'], $b['patch']);
		});

		return $this->mapToEntity(end($patches));
	}
}