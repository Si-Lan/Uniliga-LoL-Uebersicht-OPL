<?php

namespace App\Entities;

use App\Enums\EventFormat;
use App\Enums\EventType;

class Tournament {
	/**
	 * @param int $id
	 * @param int|null $idParent
	 * @param int|null $idTopParent
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
		public ?int $idParent,
		public ?int $idTopParent,
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
}