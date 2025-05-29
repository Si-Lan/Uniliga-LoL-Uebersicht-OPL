<?php

namespace App\Domain\Repositories;

use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Entities\RankedSplit;
use App\Domain\Entities\Tournament;
use App\Domain\Enums\EventType;

class TournamentRepository extends AbstractRepository {
	use DataParsingHelpers;
	private RankedSplitRepository $rankedSplitRepo;

	protected static array $ALL_DATA_KEYS = ["OPL_ID","OPL_ID_parent","OPL_ID_top_parent","name","split","season","eventType","format","number","numberRangeTo","dateStart","dateEnd","OPL_ID_logo","finished","deactivated","archived","ranked_season","ranked_split"];
	protected static array $REQUIRED_DATA_KEYS = ["OPL_ID","name"];
	private array $cache = [];

	public function __construct() {
		parent::__construct();
		$this->rankedSplitRepo = new RankedSplitRepository();
	}

	public function mapToEntity(array $data, ?Tournament $directParentTournament=null, ?Tournament $rootTournament=null, ?RankedSplit $rankedSplit=null): Tournament {
		$data = $this->normalizeData($data);
		if (is_null($rootTournament)) {
			if (!is_null($data["OPL_ID_top_parent"]) && $data["eventType"] !== EventType::TOURNAMENT->value) {
				$rootTournament = $this->findById($data["OPL_ID_top_parent"]);
			}
		}
		$rankedSplit = !is_null($rootTournament) ? $rootTournament->rankedSplit : $rankedSplit;
		if (is_null($rankedSplit) && !is_null($data["ranked_season"])) {
			$rankedSplit = $this->rankedSplitRepo->findBySeasonAndSplit($data["ranked_season"], $data["ranked_split"]);
		}
		if (is_null($directParentTournament)) {
			if (!is_null($data["OPL_ID_parent"]) && $data["eventType"] !== EventType::TOURNAMENT->value) {
				if (!is_null($rootTournament) && $data["OPL_ID_parent"] === $rootTournament->id) {
					$directParentTournament = $rootTournament;
				} else {
					$directParentTournament = $this->findById($data["OPL_ID_parent"], rootParent: $rootTournament);
				}
			}
		}
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
			logoId: $this->intOrNull($data['OPL_ID_logo']),
			finished: (bool) $data['finished']??false,
			deactivated: (bool) $data['deactivated']??false,
			archived: (bool) $data['archived']??false,
			rankedSplit: $rankedSplit,
			userSelectedRankedSplit: null,
			mostCommonBestOf: null
		);
		if ($data["eventType"] !== EventType::TOURNAMENT->value && $rootTournament !== null) {
			$tournament->userSelectedRankedSplit = $rootTournament->userSelectedRankedSplit;
		} elseif ($data["eventType"] === EventType::TOURNAMENT->value) {
			$tournament->userSelectedRankedSplit = $this->rankedSplitRepo->findSelectedSplitForTournament($tournament);
		}
		if ($tournament->isEventWithStanding()) $mostCommonBestOf = $this->dbcn->execute_query("SELECT bestOf, SUM(bestOf) AS amount FROM matchups WHERE OPL_ID_tournament = ? GROUP BY bestOf ORDER BY amount DESC",[$data["OPL_ID"]])->fetch_column();
		$tournament->mostCommonBestOf = $this->intOrNull($mostCommonBestOf??null);
		return $tournament;
	}

	public function findById(int $tournamentId, ?Tournament $directParent=null, ?Tournament $rootParent=null) : ?Tournament {
		if (isset($this->cache[$tournamentId])) {
			return $this->cache[$tournamentId];
		}
		$result = $this->dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournamentId]);
		$data = $result->fetch_assoc();

		$tournament = $data ? $this->mapToEntity($data,$directParent,$rootParent) : null;
		$this->cache[$tournamentId] = $tournament;

		return $tournament;
	}

	public function findStandingsEventById(int $tournamentId, ?Tournament $directParent=null, ?Tournament $rootParent=null) : ?Tournament {
		$result = $this->dbcn->execute_query("SELECT * FROM events_with_standings WHERE OPL_ID = ?", [$tournamentId]);
		$data = $result->fetch_assoc();

		if ($data && isset($this->cache[$tournamentId])) {
			return $this->cache[$tournamentId];
		}

		$tournament = $data ? $this->mapToEntity($data,$directParent,$rootParent) : null;
		$this->cache[$tournamentId] = $tournament;

		return $tournament;
	}

	public function tournamentExists(int $tournamentId, EventType $eventType): bool {
		$result = $this->dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ? AND eventType = ?", [$tournamentId, $eventType->value]);
		$data = $result->fetch_assoc();
		return $data !== null;
	}

	/**
	 * @return array<Tournament>
	 */
	public function findAllRootTournaments(): array {
		$query = "SELECT * FROM tournaments WHERE eventType = ?";
		$result = $this->dbcn->execute_query($query,[EventType::TOURNAMENT->value]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$tournaments = [];
		foreach ($data as $tournamentData) {
			$tournaments[] = $this->mapToEntity($tournamentData);
		}
		return $tournaments;
	}

	/**
	 * @param Tournament $rootTournament
	 * @param EventType $type
	 * @return array<Tournament>
	 */
	public function findAllByRootTournamentAndType(Tournament $rootTournament, EventType $type):array {
		$query = 'SELECT * FROM tournaments WHERE OPL_ID_top_parent = ? AND eventType = ? ORDER BY number';
		$result = $this->dbcn->execute_query($query,[$rootTournament->id,$type->value]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$tournaments = [];
		foreach ($data as $tournamentData) {
			$tournaments[] = $this->mapToEntity($tournamentData, rootTournament: $rootTournament);
		}
		return $tournaments;
	}
	/**
	 * @param Tournament $parentTournament
	 * @param EventType $type
	 * @return array<Tournament>
	 */
	public function findAllByParentTournamentAndType(Tournament $parentTournament, EventType $type):array {
		$query = 'SELECT * FROM tournaments WHERE OPL_ID_parent = ? AND eventType = ? ORDER BY number';
		$result = $this->dbcn->execute_query($query,[$parentTournament->id,$type->value]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$tournaments = [];
		foreach ($data as $tournamentData) {
			$tournaments[] = $this->mapToEntity($tournamentData, directParentTournament: $parentTournament);
		}
		return $tournaments;
	}

	/**
	 * @param Tournament $rootTournament
	 * @return array<Tournament>
	 */
	public function findAllGroupsByRootTournament(Tournament $rootTournament):array {
		$query = 'SELECT * FROM events_in_groupstage WHERE OPL_ID_top_parent = ? ORDER BY number';
		$result = $this->dbcn->execute_query($query,[$rootTournament->id]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$tournaments = [];
		foreach ($data as $tournamentData) {
			$tournaments[] = $this->mapToEntity($tournamentData, rootTournament: $rootTournament);
		}
		return $tournaments;
	}
}