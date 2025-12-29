<?php

namespace App\Domain\Factories;

use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Entities\Tournament;
use App\Domain\Enums\EventType;
use App\Domain\Repositories\RankedSplitRepository;

class TournamentFactory {
    use DataParsingHelpers;

	protected static array $DB_DATA_KEYS = [
		'OPL_ID',
		'OPL_ID_parent',
		'OPL_ID_top_parent',
		'name',
		'split',
		'season',
		'eventType',
		'format',
		'number',
		'numberRangeTo',
		'dateStart',
		'dateEnd',
		'OPL_ID_logo',
		'finished',
		'deactivated',
		'archived',
		'last_cron_update'
	];
	protected static array $REQUIRED_DB_DATA_KEYS = [
		'OPL_ID',
		'name'
	];

	public function __construct(
		private ?RankedSplitRepository $rankedSplitRepo = null
	) {
		if ($this->rankedSplitRepo === null) $this->rankedSplitRepo = new RankedSplitRepository();
	}

	/**
	 * @throws \Exception
	 */
	protected function normalizeDbData(array $data): array {
		$defaults = array_fill_keys(static::$DB_DATA_KEYS, null);
		$normalizedData = array_merge($defaults, $data);
		foreach (static::$REQUIRED_DB_DATA_KEYS as $key) {
			if (is_null($normalizedData[$key])) {
				throw new \Exception("Missing required key '$key'");
			}
		}
		return $normalizedData;
	}

	public function createFromDbData(
		array $data,
		Tournament|array|null $directParentData,
		Tournament|array|null $rootParentData,
		?int $mostCommonBestOf,
		array $rankedSplits = [],
		bool $newEntity = false
	): Tournament{
        $data = $this->normalizeDbData($data);
		$directParent = $directParentData instanceof Tournament ? $directParentData : null;
		$rootParent = $rootParentData instanceof Tournament ? $rootParentData : null;

		if ($rootParent === null && $rootParentData !== null) {
			$rootParent = $this->createFromDbData($rootParentData, null, null, null);
		}

		if ($directParent === null && $directParentData !== null) {
			$directParentParent = $directParentData['OPL_ID_parent'] === $rootParent->id ? $rootParent : null;
			$directParent = $this->createFromDbData($directParentData, $directParentParent, $rootParent, null);
		}

		$rankedSplits = ($rootParent !== null) ? $rootParent->rankedSplits : $rankedSplits;
		if ($newEntity && $data['eventType'] === EventType::TOURNAMENT->value) {
			$rankedSplits = [];
			foreach ($data['ranked_splits'] ?? [] as $rankedSplit) {
				$rankedSplit_exploded = explode('-', $rankedSplit);
				$rankedSplit_season = $this->intOrNull($rankedSplit_exploded[0]);
				$rankedSplit_split = $this->intOrNull($rankedSplit_exploded[1]??null);
				$rankedSplits[] = $this->rankedSplitRepo->findBySeasonAndSplit($rankedSplit_season, $rankedSplit_split);
			}
		}
		if (count($rankedSplits) === 0) {
			$rankedSplits = $this->rankedSplitRepo->findAllByTournamentId($data['OPL_ID']);
		}

		$tournament =  new Tournament(
            id: (int) $data['OPL_ID'],
			directParentTournament: $directParent,
			rootTournament: $rootParent,
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
			finished: (bool) ($data['finished'] ?? false),
			deactivated: (bool) ($data['deactivated'] ?? false),
			archived: (bool) ($data['archived'] ?? false),
			lastCronUpdate: $this->DateTimeImmutableOrNull($data['last_cron_update']),
			rankedSplits: $rankedSplits,
			userSelectedRankedSplit: null,
			mostCommonBestOf: $mostCommonBestOf
        );

		if ($newEntity) return $tournament;

		if ($data['eventType'] !== EventType::TOURNAMENT->value && $rootParent !== null) {
			$tournament->userSelectedRankedSplit = $rootParent->userSelectedRankedSplit;
		} elseif ($data['eventType'] === EventType::TOURNAMENT->value) {
			$tournament->userSelectedRankedSplit = $this->rankedSplitRepo->findSelectedSplitForTournament($tournament);
		}

		return $tournament;
    }

	public function mapEntityToDbData(Tournament $tournament): array {
		return [
			'OPL_ID' => $tournament->id,
			'OPL_ID_parent' => $tournament->directParentTournament?->id,
			'OPL_ID_top_parent' => $tournament->rootTournament?->id,
			'name' => $tournament->name,
			'split' => $tournament->split,
			'season' => $tournament->season,
			'eventType' => $tournament->eventType?->value,
			'format' => $tournament->format?->value,
			'number' => $tournament->number,
			'numberRangeTo' => $tournament->numberRangeTo,
			'dateStart' => $tournament->dateStart?->format('Y-m-d'),
			'dateEnd' => $tournament->dateEnd?->format('Y-m-d'),
			'OPL_ID_logo' => $tournament->logoId,
			'finished' => $tournament->finished ? 1 : 0,
			'deactivated' => $tournament->deactivated ? 1 : 0,
			'archived' => $tournament->archived ? 1 : 0
		];
	}

	public function createFromOplData(array $oplData): Tournament {
		$tournamentEntity = new Tournament(
			id: (int) $oplData['ID'],
			directParentTournament: null,
			rootTournament: null,
			name: (string) $oplData['name'],
			split: null,
			season: null,
			eventType: null,
			format: null,
			number: null,
			numberRangeTo: null,
			dateStart: $this->DateTimeImmutableOrNull($oplData['start_on']['date']??null),
			dateEnd: $this->DateTimeImmutableOrNull($oplData['end_on']['date']??null),
			logoId: null,
			finished: (bool) ($oplData['finished'] ?? false),
			deactivated: (bool) ($oplData['deactivated'] ?? true),
			archived: (bool) ($oplData['archived'] ?? false),
			lastCronUpdate: null,
			rankedSplits: [],
			userSelectedRankedSplit: null,
			mostCommonBestOf: null
		);

		$logo_url = $oplData["logo_array"]["background"] ?? null;
		$logo_id = ($logo_url != null) ? explode("/", $logo_url, -1) : null;
		$logo_id = ($logo_id != null) ? end($logo_id) : null;
		$tournamentEntity->logoId = $logo_id;

		$name_lower = strtolower($oplData['name']);

		$possible_splits = ["winter", "sommer"];
		foreach ($possible_splits as $possible_split) {
			if (str_contains($name_lower, $possible_split)) {
				$tournamentEntity->split = $possible_split;
			}
		}

		if (preg_match("/(?:winter|sommer)(?:season|saison)? *[0-9]*([0-9]{2})/",$name_lower,$season_match)) {
			$tournamentEntity->season = $this->intOrNull($season_match[1]);
		}

		$tournamentEntity->eventType = EventType::fromName($name_lower);

		switch ($tournamentEntity->eventType) {
			case EventType::LEAGUE:
			case EventType::WILDCARD:
			case EventType::PLAYOFFS:
				// Matcht: "Liga 1", "Liga 2-5", "Liga 1./2."
				if (preg_match('/\bliga\s*(\d)(?:\D+(\d))?/', $name_lower, $matches)) {
					$tournamentEntity->number = $this->intOrNull($matches[1]);
					$tournamentEntity->numberRangeTo = isset($matches[2]) ? $this->intOrNull($matches[2]) : null;
				}
				// Matcht: "1. Liga", "1./2. Liga"
				if (preg_match('/\b(\d+)(?:\W+(\d+))?\.?\s*liga\b/', $name_lower, $matches)) {
					$tournamentEntity->number = $this->intOrNull($matches[1]);
					$tournamentEntity->numberRangeTo = isset($matches[2]) ? $this->intOrNull($matches[2]) : null;
				}
				break;

			case EventType::GROUP:
				// Matcht "Gruppe A" oder "Group A"
				if (preg_match('/\b(?:gruppe|group)\s+([a-z])/', $name_lower, $matches)) {
					$tournamentEntity->number = $this->stringOrNull(strtoupper($matches[1]??""));
				}
				break;
			case EventType::TOURNAMENT:
				break;
		}

		return $tournamentEntity;
	}
}