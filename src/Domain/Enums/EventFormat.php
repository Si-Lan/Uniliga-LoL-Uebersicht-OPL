<?php

namespace App\Domain\Enums;

enum EventFormat: string {
	case ROUND_ROBIN = 'round-robin';
	case SINGLE_ELIMINATION = 'single-elimination';
	case DOUBLE_ELIMINATION = 'double-elimination';
	case SWISS = 'swiss';
}
