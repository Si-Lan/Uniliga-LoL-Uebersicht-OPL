<?php

namespace App\Domain\Entities;

use App\Domain\Enums\EventFormat;
use App\Domain\Enums\EventType;

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
		public ?int $logoId,
		public bool $finished,
		public bool $deactivated,
		public bool $archived,
		public ?RankedSplit $rankedSplit,
		public ?RankedSplit $userSelectedRankedSplit,
		public ?int $mostCommonBestOf
	) {}

	public function getDirectParentTournament(): Tournament {
		if (is_null($this->directParentTournament)) return $this;
		return $this->directParentTournament;
	}
	public function getRootTournament(): Tournament {
		if (is_null($this->rootTournament)) return $this;
		return $this->rootTournament;
	}

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

	public function isRunning():bool {
		$today = new \DateTimeImmutable();
		return (is_null($this->dateEnd) || $this->dateEnd > $today);
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
	public function getHref(): string {
		if ($this->eventType == EventType::TOURNAMENT) {
			return "/turnier/".$this->id;
		}
		$urlKey = $this->getUrlKey();
		if ($this->eventType === EventType::LEAGUE && $this->format === EventFormat::SWISS) {
			$urlKey = 'gruppe';
		}
		return "/turnier/{$this->rootTournament->id}/$urlKey/{$this->id}";
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
			EventType::PLAYOFFS => "Playoffs Liga ".$this->getNumberFormatted(),
			default => "",
		};
	}
	public function getShortName():string {
		if ($this->eventType === EventType::LEAGUE && $this->format === EventFormat::SWISS) {
			return "Liga ".$this->getNumberFormatted();
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
	public function getSplitAndSeason():string {
		return ucfirst($this->split)." ".$this->season;
	}

	public function getLogoUrl() : string|false {
		if (is_null($this->logoId)) return false;
		$baseUrl = "/img/tournament_logos/{$this->logoId}/";
		if (!file_exists(BASE_PATH.'/public'.$baseUrl)) return false;
		if (isset($_COOKIE['lightmode']) && $_COOKIE['lightmode'] === "1") {
			return $baseUrl."logo_light.webp";
		} else {
			return $baseUrl."logo.webp";
		}
	}
}