<?php

namespace App\Domain\Enums;

enum EventType: string {
	case TOURNAMENT = 'tournament';
	case LEAGUE = 'league';
	case GROUP = 'group';
	case WILDCARD = 'wildcard';
	case PLAYOFFS = 'playoffs';

	public function getPrettyName(): string {
		return match ($this) {
			self::TOURNAMENT => "Turnier",
			self::LEAGUE => "Liga",
			self::GROUP => "Gruppe",
			self::WILDCARD => "Wildcard",
			self::PLAYOFFS => "Playoffs",
		};
	}
	public function getPrettyNamePlural(): string {
		return match ($this) {
			self::TOURNAMENT => "Turniere",
			self::LEAGUE => "Ligen",
			self::GROUP => "Gruppen",
			self::WILDCARD => "Wildcards",
			self::PLAYOFFS => "Playoffs",
		};
	}

	public function hasChildren(): string {
		return ($this === self::LEAGUE || $this === self::TOURNAMENT);
	}

	public static function fromName(string $name): ?EventType {
		$name = mb_strtolower($name);
		if (str_contains($name, "wildcard")) {
			return self::WILDCARD;
		}
		if (str_contains($name, "playoff")) {
			return self::PLAYOFFS;
		}
		if (preg_match('/gruppe\s+[a-z1-9]/i', $name) || preg_match('/group\s+[a-z1-9]/i', $name)) {
			return self::GROUP;
		}
		if (preg_match("/\bliga\s+\d+/",$name) || preg_match("/\b(\d+)\.?\s*liga\b/",$name)) {
			return self::LEAGUE;
		}
		return self::TOURNAMENT;
	}
}
