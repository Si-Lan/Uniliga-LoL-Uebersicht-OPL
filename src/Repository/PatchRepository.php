<?php

namespace App\Repository;

use App\Database\DatabaseConnection;
use App\Entities\Patch;

class PatchRepository {
	private \mysqli $dbcn;

	public function __construct() {
		$this->dbcn = DatabaseConnection::getConnection();
	}

	private function createEntityFromData(array $data): Patch {
		return new Patch(
			patchNumber: $data['patch'],
			data: (bool) $data['data'],
			championWebp: (bool) $data['champion_webp'],
			itemWebp: (bool) $data['item_webp'],
			spellWebp: (bool) $data['spell_webp'],
			runesWebp: (bool) $data['runes_webp']
		);
	}

	public function getLatestPatchWithAllData(): Patch {
		$patches = $this->dbcn->execute_query("SELECT * FROM local_patches WHERE data IS TRUE AND champion_webp IS TRUE AND item_webp IS TRUE AND runes_webp IS TRUE AND spell_webp IS TRUE")->fetch_all(MYSQLI_ASSOC);
		usort($patches, function($a, $b) {
			return version_compare($a['patch'], $b['patch']);
		});

		$latestPatch = $this->createEntityFromData(end($patches));
		return $latestPatch;
	}
}