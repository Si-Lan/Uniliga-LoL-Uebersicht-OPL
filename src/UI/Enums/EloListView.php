<?php

namespace App\UI\Enums;

use App\Domain\Entities\Tournament;

enum EloListView: string {
	case ALL = 'all';
	case BY_LEAGUES = 'div';
	case BY_GROUPS = 'group';
	case WILDCARD_ALL = 'all-wildcard';
	case WILDCARD_BY_LEAGUES = 'wildcard';

	public function getClassName(): string {
		return $this->value."-teams";
	}

	public function isWildcard(): bool {
		return $this === self::WILDCARD_ALL || $this === self::WILDCARD_BY_LEAGUES;
	}
	public function isGroupStage(): bool {
		return $this === self::ALL || $this === self::BY_LEAGUES || $this === self::BY_GROUPS;
	}
	public function isLeagueColored(): bool {
		return $this === self::ALL || $this === self::WILDCARD_ALL;
	}

	public function getHeading(Tournament $tournamentStage): string {
		return match ($this) {
			self::ALL => 'Alle Ligen',
			self::BY_LEAGUES, self::BY_GROUPS, self::WILDCARD_BY_LEAGUES => $tournamentStage->getFullName(),
			self::WILDCARD_ALL => 'Wildcard-Turniere',
		};
	}
}
