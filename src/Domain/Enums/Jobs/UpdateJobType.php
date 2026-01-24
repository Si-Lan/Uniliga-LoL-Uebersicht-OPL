<?php

namespace App\Domain\Enums\Jobs;

enum UpdateJobType: string {
	case ADMIN = 'admin';
	case USER = 'user';
	case CRON = 'cron';
}
