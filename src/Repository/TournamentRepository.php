<?php

namespace App\Repository;

use App\Database\DatabaseConnection;
use App\Entities\Tournament;

class TournamentRepository {
	private \mysqli $dbcn;

	public function __construct() {
		$this->dbcn = DatabaseConnection::getConnection();
	}

	public function findById(int $tournamentId) : ?Tournament {
		$result = $this->dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournamentId]);
		$data = $result->fetch_assoc();

		$tournament = $data ? new Tournament(
			id: $data['OPL_ID'],
			idParent: $data['OPL_ID_parent'],
			idTopParent: $data['OPL_ID_top_parent'],
			name: $data['name'],
			split: $data['split'],
			season: $data['season'],
			event: $data['eventType'],
			format: $data['format'],
			number: $data['number'],
			numberRangeTo: $data['numberRangeTo'],
			dateStart: new \DateTimeImmutable($data['dateStart']),
			dateEnd: new \DateTimeImmutable($data['dateEnd']),
			logoUrl: $data['OPL_logo_url'],
			logoId: $data['OPL_ID_logo'],
			finished: $data['finished'],
			deactivated: $data['deactivated'],
			archived: $data['archived'],
			rankedSeason: $data['ranked_season'],
			rankedSplit: $data['ranked_split']
		) : null;

		return $tournament;
	}
}