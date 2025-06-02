<?php

namespace App\Domain\Enums;

enum SaveResult {
	case INSERTED;
	case UPDATED;
	case FAILED;
}
