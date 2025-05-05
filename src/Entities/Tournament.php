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
	 * @param int|null $rankedSeason
	 * @param int|null $rankedSplit
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
		public ?int $rankedSeason,
		public ?int $rankedSplit
	) {}

	public function isEventWithStanding():bool {
		if ($this->eventType === EventType::GROUP) return true;
		if ($this->eventType === EventType::LEAGUE && $this->format === EventFormat::SWISS) return true;
		if ($this->eventType === EventType::WILDCARD) return true;
		if ($this->eventType === EventType::PLAYOFFS) return true;
		return false;
	}

	public function getNumberFormatted():string {
		if (is_null($this->number)) return "";
		if (is_null($this->numberRangeTo)) return $this->number;
		return $this->number."-".$this->numberRangeTo;
	}
	public function getShortenedTournamentName():string {
		return preg_replace("/LoL\s/i","",$this->name);
	}

	public function getFullSubStageName():string {
		if ($this->eventType === EventType::TOURNAMENT) {
			return $this->name;
		}
		if ($this->eventType === EventType::LEAGUE) {
			return "Liga ".$this->getNumberFormatted();
		}
		if ($this->eventType === EventType::GROUP) {
			return "Liga ".$this->directParentTournament->getNumberFormatted()." / Gruppe ".$this->getNumberFormatted();
		}
		if ($this->eventType === EventType::WILDCARD) {
			return "Wildcard-Turnier Liga ".$this->getNumberFormatted();
		}
		if ($this->eventType === EventType::PLAYOFFS) {
			return "Playoffs Liga".$this->getNumberFormatted();
		}
		return "";
	}
	public function getShortSubStageName():string {
		if ($this->eventType === EventType::TOURNAMENT) {
			return $this->getShortenedTournamentName();
		}
		if ($this->eventType === EventType::LEAGUE) {
			return "Liga ".$this->getNumberFormatted();
		}
		if ($this->eventType === EventType::GROUP) {
			return "Gruppe ".$this->getNumberFormatted();
		}
		if ($this->eventType === EventType::WILDCARD) {
			return "Wildcard Liga ".$this->getNumberFormatted();
		}
		if ($this->eventType === EventType::PLAYOFFS) {
			return "Playoffs Liga".$this->getNumberFormatted();
		}
		return "";
	}
}