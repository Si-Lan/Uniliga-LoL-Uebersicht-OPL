<?php

namespace App\Domain\Enums;

enum SuggestionStatus: string {
	case PENDING = 'pending';
	case ACCEPTED = 'accepted';
	case REJECTED = 'rejected';
}
