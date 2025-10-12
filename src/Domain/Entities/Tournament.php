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
	public function isStage():bool {
		return $this->isEventWithStanding();
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
	public function getLeadingLeagueNumber(): ?int {
		return match ($this->eventType) {
			EventType::LEAGUE, EventType::WILDCARD => $this->number,
			EventType::GROUP => $this->directParentTournament->number,
			default => null
		};
	}
	public function getLeagueName(): string {
		return match ($this->eventType) {
			EventType::LEAGUE => $this->getShortName(),
			EventType::GROUP => "Liga ".$this->directParentTournament->getNumberFormatted(),
			EventType::WILDCARD => "Liga ".$this->getNumberFormatted(),
			default => ''
		};
	}
	public function getSplitAndSeason():string {
		return ucfirst($this->split)." ".$this->season;
	}

	public function getLogoUrl() : string|false {
		if (is_null($this->logoId)) return false;
		$baseUrl = "/assets/img/tournament_logos/{$this->logoId}/";
		if (!file_exists(BASE_PATH.'/public'.$baseUrl)) return false;
		if (isset($_COOKIE['lightmode']) && $_COOKIE['lightmode'] === "1") {
			return $baseUrl."logo_light.webp";
		} else {
			return $baseUrl."logo.webp";
		}
	}

	public function getDataDifference(Tournament $tournament):array {
		$diff = [];
		if ($this->id !== $tournament->id) $diff['id'] = $this->id;
		if ($this->directParentTournament?->id !== $tournament->directParentTournament?->id) $diff['directParentTournamentId'] = $this->directParentTournament?->id;
		if ($this->rootTournament?->id !== $tournament->rootTournament?->id) $diff['rootTournamentId'] = $this->rootTournament?->id;
		if ($this->name !== $tournament->name) $diff['name'] = $this->name;
		if ($this->split !== $tournament->split) $diff['split'] = $this->split;
		if ($this->season !== $tournament->season) $diff['season'] = $this->season;
		if ($this->eventType !== $tournament->eventType) $diff['eventType'] = $this->eventType;
		if ($this->format !== $tournament->format) $diff['format'] = $this->format;
		if ($this->number !== $tournament->number) $diff['number'] = $this->number;
		if ($this->numberRangeTo !== $tournament->numberRangeTo) $diff['numberRangeTo'] = $this->numberRangeTo;
		if ($this->dateStart?->format('Y-m-d') !== $tournament->dateStart?->format('Y-m-d')) $diff['dateStart'] = $this->dateStart?->format('Y-m-d');
		if ($this->dateEnd?->format('Y-m-d') !== $tournament->dateEnd?->format('Y-m-d')) $diff['dateEnd'] = $this->dateEnd?->format('Y-m-d');
		if ($this->logoId !== $tournament->logoId) $diff['logoId'] = $this->logoId;
		if ($this->finished !== $tournament->finished) $diff['finished'] = $this->finished;
		if ($this->deactivated !== $tournament->deactivated) $diff['deactivated'] = $this->deactivated;
		if ($this->archived !== $tournament->archived) $diff['archived'] = $this->archived;
		if ($this->eventType === EventType::TOURNAMENT && !$this->rankedSplit?->equals($tournament->rankedSplit)) $diff['rankedSplit'] = $this->rankedSplit?->getName();
		return $diff;
	}
}