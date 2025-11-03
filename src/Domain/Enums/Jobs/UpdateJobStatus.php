<?php

namespace App\Domain\Enums\Jobs;

enum UpdateJobStatus: string {
	case QUEUED = 'queued';
	case RUNNING = 'running';
	case SUCCESS = 'success';
	case ERROR = 'error';
	case CANCELLED = 'cancelled';
	case ABANDONED = 'abandoned';
}
