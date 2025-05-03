<?php

namespace App\Repository;

use App\Database\DatabaseConnection;
use App\Entity\Team;

class TeamRepository {
	private \mysqli $dbcn;

	public function __construct() {
		$this->dbcn = DatabaseConnection::getConnection();
	}

	public function findById(int $teamId): ?Team {
		$result = $this->dbcn->execute_query("SELECT * FROM teams WHERE OPL_ID = ?", [$teamId]);
		$data = $result->fetch_assoc();

		$team = $data ? new Team(
			id: $data['OPL_ID'],
			name: $data['name'],
			shortName: $data['short_name'],
			logoUrl: $data['OPL_logo_url'],
			logoId: $data['OPL_ID_logo'],
			lastLogoDownload: $data['last_logo_download'],
			avgRankTier: $data['avg_rank_tier'],
			avgRankDiv: $data['avg_rank_div'],
			avgRankNum: $data['avg_rank_num'],
		) : null;

		return $team;
	}
}