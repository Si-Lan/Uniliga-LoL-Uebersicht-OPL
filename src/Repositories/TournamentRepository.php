<?php

namespace App\Repositories;

use App\Database\DatabaseConnection;
use App\Entities\Tournament;
use App\Utilities\DataParsingHelpers;

class TournamentRepository {
	use DataParsingHelpers;

	private \mysqli $dbcn;

	public function __construct() {
		$this->dbcn = DatabaseConnection::getConnection();
	}

	public function mapToEntity(array $data): Tournament {
		return new Tournament(
			id: (int) $data['OPL_ID'],
			idParent: $this->intOrNull($data['OPL_ID_parent']??null),
			idTopParent: $this->intOrNull($data['OPL_ID_top_parent']??null),
			name: (string) $data['name'],
			split: $this->stringOrNull($data['split']??null),
			season: $this->intOrNull($data['season']??null),
			event: $this->stringOrNull($data['eventType']??null),
			format: $this->stringOrNull($data['format']??null),
			number: $this->stringOrNull($data['number']??null),
			numberRangeTo: $this->stringOrNull($data['numberRangeTo']??null),
			dateStart: $this->DateTimeImmutableOrNull($data['dateStart']??null),
			dateEnd: $this->DateTimeImmutableOrNull($data['dateEnd']??null),
			logoUrl: $this->stringOrNull($data['OPL_logo_url']??null),
			logoId: $this->intOrNull($data['OPL_ID_logo']??null),
			finished: (bool) $data['finished']??false,
			deactivated: (bool) $data['deactivated']??false,
			archived: (bool) $data['archived']??false,
			rankedSeason: $this->intOrNull($data['ranked_season']??null),
			rankedSplit: $this->intOrNull($data['ranked_split']??null)
		);
	}

	public function findById(int $tournamentId) : ?Tournament {
		$result = $this->dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournamentId]);
		$data = $result->fetch_assoc();

		$tournament = $data ? $this->mapToEntity($data) : null;

		return $tournament;
	}
}