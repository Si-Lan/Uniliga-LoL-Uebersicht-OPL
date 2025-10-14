<?php

namespace App\Domain\Enums;

enum SaveResult: string {
	case INSERTED = 'inserted';
	case UPDATED = 'updated';
	case FAILED = 'failed';
	case NOT_CHANGED = 'not-changed';
}
