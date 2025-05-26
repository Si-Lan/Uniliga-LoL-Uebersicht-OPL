<?php

namespace App\Domain\Repositories;

use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Entities\Team;
use App\Domain\Entities\ValueObjects\RankAverage;

class TeamRepository extends AbstractRepository {
	use DataParsingHelpers;

	protected static array $ALL_DATA_KEYS = ["OPL_ID","name","shortName","OPL_ID_logo","last_logo_download","avg_rank_tier","avg_rank_div","avg_rank_num"];
	protected static array $REQUIRED_DATA_KEYS = ["OPL_ID","name"];

	public function mapToEntity(array $data): Team {
		$data = $this->normalizeData($data);
		return new Team(
			id: (int) $data['OPL_ID'],
			name: (string) $data['name'],
			shortName: $this->stringOrNull($data['shortName']),
			logoId: $this->intOrNull($data['OPL_ID_logo']),
			lastLogoDownload: $this->DateTimeImmutableOrNull($data['last_logo_download']),
			rank: new RankAverage(
				$this->stringOrNull($data['avg_rank_tier']),
				$this->stringOrNull($data['avg_rank_div']),
				$this->floatOrNull($data['avg_rank_num']),
			)
		);
	}

	public function findById(int $teamId): ?Team {
		$result = $this->dbcn->execute_query("SELECT * FROM teams WHERE OPL_ID = ?", [$teamId]);
		$data = $result->fetch_assoc();

		return $data ? $this->mapToEntity($data) : null;
	}

	public function teamExists(int $teamId): bool {
		$result = $this->dbcn->execute_query("SELECT * FROM teams WHERE OPL_ID = ?", [$teamId]);
		$data = $result->fetch_assoc();
		return $data !== null;
	}
}