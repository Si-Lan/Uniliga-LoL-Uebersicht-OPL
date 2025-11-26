<?php
include dirname(__DIR__,2) . '/bootstrap.php';

use App\Domain\Entities\UpdateJob;
use App\Domain\Enums\Jobs\UpdateJobStatus;
use App\Domain\Repositories\UpdateJobRepository;

$jobRepo = new UpdateJobRepository();

$oldJobsWithMessage = $jobRepo->findOldJobsWithMessage();

foreach ($oldJobsWithMessage as $job) {
	$job->message = null;
	$jobRepo->save($job);
	echo "Cleared message for job {$job->id}\n";
}
