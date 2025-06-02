<?php

namespace App\Domain\Enums;

enum EventType: string {
	case TOURNAMENT = 'tournament';
	case LEAGUE = 'league';
	case GROUP = 'group';
	case WILDCARD = 'wildcard';
	case PLAYOFFS = 'playoffs';

	public static function fromName(string $name): ?EventType {
		$name = mb_strtolower($name);
		if (str_contains($name, "wildcard")) {
			return self::WILDCARD;
		}
		if (str_contains("playoff",$name)) {
			return self::PLAYOFFS;
		}
		if (preg_match('/gruppe\s+[a-z1-9]/i', $name) || preg_match('/group\s+[a-z1-9]/i', $name)) {
			return self::GROUP;
		}
		if (preg_match("/\bliga\s+\d+/",$name)) {
			return self::LEAGUE;
		}
		return self::TOURNAMENT;
	}
}
