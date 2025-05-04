<?php

namespace App\Repositories;

use App\Database\DatabaseConnection;
use App\Entities\Team;
use App\Utilities\NullableCastTrait;

class TeamRepository {
	use NullableCastTrait;

	private \mysqli $dbcn;

	public function __construct() {
		$this->dbcn = DatabaseConnection::getConnection();
	}

	public function createEntityFromData(array $data): Team {
		return new Team(
			id: (int) $data['OPL_ID'],
			name: (string) $data['name'],
			shortName: $this->nullableString($data['shortName']),
			logoUrl: $this->nullableString($data['OPL_logo_url']),
			logoId: $this->nullableInt($data['OPL_ID_logo']),
			lastLogoDownload: $this->nullableDateTime($data['last_logo_download']),
			avgRankTier: $this->nullableString($data['avg_rank_tier']),
			avgRankDiv: $this->nullableString($data['avg_rank_div']),
			avgRankNum: $this->nullableFloat($data['avg_rank_num']),
		);
	}

	public function findById(int $teamId): ?Team {
		$result = $this->dbcn->execute_query("SELECT * FROM teams WHERE OPL_ID = ?", [$teamId]);
		$data = $result->fetch_assoc();

		$team = $data ? $this->createEntityFromData($data) : null;

		return $team;
	}
}