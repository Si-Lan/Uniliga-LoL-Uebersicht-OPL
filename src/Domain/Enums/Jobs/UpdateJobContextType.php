<?php

namespace App\Domain\Enums\Jobs;

enum UpdateJobContextType: string {
	case TOURNAMENT = 'tournament';
	case TEAM = 'team';
	case GROUP = 'group';
	case MATCHUP = 'matchup';
}
