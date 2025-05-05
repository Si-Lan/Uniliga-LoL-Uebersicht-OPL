<?php

namespace App\Repositories;

use App\Entities\Tournament;
use App\Utilities\DataParsingHelpers;

class TournamentRepository extends AbstractRepository {
	use DataParsingHelpers;

	protected static array $ALL_DATA_KEYS = ["OPL_ID","OPL_ID_parent","OPL_ID_top_parent","name","split","season","eventType","format","number","numberRangeTo","dateStart","dateEnd","OPL_logo_url","OPL_ID_logo","finished","deactivated","archived","ranked_season","ranked_split"];
	protected static array $REQUIRED_DATA_KEYS = ["OPL_ID","name"];

	public function mapToEntity(array $data): Tournament {
		$data = $this->normalizeData($data);
		return new Tournament(
			id: (int) $data['OPL_ID'],
			idParent: $this->intOrNull($data['OPL_ID_parent']),
			idTopParent: $this->intOrNull($data['OPL_ID_top_parent']),
			name: (string) $data['name'],
			split: $this->stringOrNull($data['split']),
			season: $this->intOrNull($data['season']),
			eventType: $this->EventTypeEnumOrNull($data['eventType']),
			format: $this->EventFormatEnumOrNull($data['format']),
			number: $this->stringOrNull($data['number']),
			numberRangeTo: $this->stringOrNull($data['numberRangeTo']),
			dateStart: $this->DateTimeImmutableOrNull($data['dateStart']),
			dateEnd: $this->DateTimeImmutableOrNull($data['dateEnd']),
			logoUrl: $this->stringOrNull($data['OPL_logo_url']),
			logoId: $this->intOrNull($data['OPL_ID_logo']),
			finished: (bool) $data['finished']??false,
			deactivated: (bool) $data['deactivated']??false,
			archived: (bool) $data['archived']??false,
			rankedSeason: $this->intOrNull($data['ranked_season']),
			rankedSplit: $this->intOrNull($data['ranked_split'])
		);
	}

	public function findById(int $tournamentId) : ?Tournament {
		$result = $this->dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournamentId]);
		$data = $result->fetch_assoc();

		return $data ? $this->mapToEntity($data) : null;
	}
}