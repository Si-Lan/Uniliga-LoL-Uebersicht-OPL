<?php

namespace App\Entities;

use App\Enums\EventFormat;
use App\Enums\EventType;

class Tournament {
	/**
	 * @param int $id
	 * @param Tournament|null $directParentTournament
	 * @param Tournament|null $rootTournament
	 * @param string $name
	 * @param 'sommmer'|'winter'|null $split
	 * @param int|null $season
	 * @param EventType|null $eventType
	 * @param EventFormat|null $format
	 * @param string|null $number
	 * @param string|null $numberRangeTo
	 * @param \DateTimeImmutable|null $dateStart
	 * @param \DateTimeImmutable|null $dateEnd
	 * @param string|null $logoUrl
	 * @param int|null $logoId
	 * @param bool $finished
	 * @param bool $deactivated
	 * @param bool $archived
	 * @param RankedSplit|null $rankedSplit
	 * @param RankedSplit|null $userSelectedRankedSplit
	 * @param int|null $mostCommonBestOf
	 */
	public function __construct(
		public int $id,
		public ?Tournament $directParentTournament,
		public ?Tournament $rootTournament,
		public string $name,
		public ?string $split,
		public ?int $season,
		public ?EventType $eventType,
		public ?EventFormat $format,
		public ?string $number,
		public ?string $numberRangeTo,
		public ?\DateTimeImmutable $dateStart,
		public ?\DateTimeImmutable $dateEnd,
		public ?string $logoUrl,
		public ?int $logoId,
		public bool $finished,
		public bool $deactivated,
		public bool $archived,
		public ?RankedSplit $rankedSplit,
		public ?RankedSplit $userSelectedRankedSplit,
		public ?int $mostCommonBestOf
	) {}

	public function isEventWithStanding():bool {
		if ($this->eventType === EventType::GROUP) return true;
		if ($this->eventType === EventType::LEAGUE && $this->format === EventFormat::SWISS) return true;
		if ($this->eventType === EventType::WILDCARD) return true;
		if ($this->eventType === EventType::PLAYOFFS) return true;
		return false;
	}

	public function isEventWithRounds():bool {
		if ($this->format === EventFormat::ROUND_ROBIN) return true;
		if ($this->format === EventFormat::SWISS) return true;
		return false;
	}

	public function getUrlKey():string {
		return match ($this->eventType) {
			EventType::TOURNAMENT => "turnier",
			EventType::LEAGUE => "liga",
			EventType::GROUP => "gruppe",
			EventType::WILDCARD => "wildcard",
			EventType::PLAYOFFS => "playoffs",
			default => "",
		};
	}

	public function getNumberFormatted():string {
		if (is_null($this->number)) return "";
		if (is_null($this->numberRangeTo)) return $this->number;
		return $this->number."-".$this->numberRangeTo;
	}

	public function getFullName():string {
		if ($this->eventType === EventType::LEAGUE && $this->format === EventFormat::SWISS) {
			return "Liga ".$this->getNumberFormatted()." - Swiss-Gruppe";
		}
		return match ($this->eventType) {
			EventType::TOURNAMENT => $this->name,
			EventType::LEAGUE => "Liga ".$this->getNumberFormatted(),
			EventType::GROUP => "Liga ".$this->directParentTournament->getNumberFormatted()." - Gruppe ".$this->getNumberFormatted(),
			EventType::WILDCARD => "Wildcard-Turnier Liga ".$this->getNumberFormatted(),
			EventType::PLAYOFFS => "Playoffs Liga".$this->getNumberFormatted(),
			default => "",
		};
	}
	public function getShortName():string {
		if ($this->eventType === EventType::LEAGUE && $this->format === EventFormat::SWISS) {
			return "Swiss-Gruppe";
		}
		return match ($this->eventType) {
			EventType::TOURNAMENT => preg_replace("/LoL\s/i","",$this->name),
			EventType::LEAGUE => $this->getFullName(),
			EventType::GROUP => "Gruppe ".$this->getNumberFormatted(),
			EventType::WILDCARD => "Wildcard Liga ".$this->getNumberFormatted(),
			EventType::PLAYOFFS => $this->getFullName(),
			default => "",
		};
	}
}