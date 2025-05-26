<?php

namespace App\Domain\Enums;

enum EventType: string {
	case TOURNAMENT = 'tournament';
	case LEAGUE = 'league';
	case GROUP = 'group';
	case WILDCARD = 'wildcard';
	case PLAYOFFS = 'playoffs';
}
