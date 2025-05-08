<?php

namespace App\Repositories;

use App\Entities\RankedSplit;
use App\Entities\Tournament;
use App\Enums\EventType;
use App\Utilities\DataParsingHelpers;

class TournamentRepository extends AbstractRepository {
	use DataParsingHelpers;
	private RankedSplitRepository $rankedSplitRepo;

	protected static array $ALL_DATA_KEYS = ["OPL_ID","OPL_ID_parent","OPL_ID_top_parent","name","split","season","eventType","format","number","numberRangeTo","dateStart","dateEnd","OPL_logo_url","OPL_ID_logo","finished","deactivated","archived","ranked_season","ranked_split"];
	protected static array $REQUIRED_DATA_KEYS = ["OPL_ID","name"];

	public function __construct() {
		parent::__construct();
		$this->rankedSplitRepo = new RankedSplitRepository();
	}

	public function mapToEntity(array $data, ?Tournament $directParentTournament=null, ?Tournament $rootTournament=null, ?RankedSplit $rankedSplit=null): Tournament {
		$data = $this->normalizeData($data);
		if (is_null($directParentTournament)) {
			if (!is_null($data["OPL_ID_parent"]) && $data["eventType"] !== EventType::TOURNAMENT->value) {
				$directParentTournament = $this->findById($data["OPL_ID_parent"]);
			}
		}
		if (is_null($rootTournament)) {
			if (!is_null($data["OPL_ID_top_parent"]) && $data["eventType"] !== EventType::TOURNAMENT->value) {
				$rootTournament = $this->findById($data["OPL_ID_top_parent"]);
			}
		}
		$rankedSplit = !is_null($rootTournament) ? $rootTournament->rankedSplit : $rankedSplit;
		if (is_null($rankedSplit) && !is_null($data["ranked_season"])) {
				$rankedSplit = $this->rankedSplitRepo->findBySeasonAndSplit($data["ranked_season"], $data["ranked_split"]);
		}
		$mostCommonBestOf = $this->dbcn->execute_query("SELECT bestOf, SUM(bestOf) AS amount FROM matchups WHERE OPL_ID_tournament = ? GROUP BY bestOf ORDER BY amount DESC",[$data["OPL_ID"]])->fetch_column();
		$tournament = new Tournament(
			id: (int) $data['OPL_ID'],
			directParentTournament: $directParentTournament,
			rootTournament: $rootTournament,
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
			rankedSplit: $rankedSplit,
			userSelectedRankedSplit: null,
			mostCommonBestOf: $this->intOrNull($mostCommonBestOf)
		);
		$tournament->userSelectedRankedSplit = $this->rankedSplitRepo->findSelectedSplitForTournament($tournament);
		return $tournament;
	}

	public function findById(int $tournamentId) : ?Tournament {
		$result = $this->dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournamentId]);
		$data = $result->fetch_assoc();

		return $data ? $this->mapToEntity($data) : null;
	}

	public function findStandingsEventById(int $tournamentId) : ?Tournament {
		$result = $this->dbcn->execute_query("SELECT * FROM events_with_standings WHERE OPL_ID = ?", [$tournamentId]);
		$data = $result->fetch_assoc();

		return $data ? $this->mapToEntity($data) : null;
	}
}