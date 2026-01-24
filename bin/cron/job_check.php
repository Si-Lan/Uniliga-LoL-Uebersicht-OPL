<?php
include dirname(__DIR__,2) . '/bootstrap.php';

use App\Domain\Entities\UpdateJob;
use App\Domain\Enums\Jobs\UpdateJobStatus;
use App\Domain\Repositories\UpdateJobRepository;

$jobRepo = new UpdateJobRepository();

$runningJobs = $jobRepo->findAll(status: UpdateJobStatus::RUNNING);

foreach ($runningJobs as $runningJob) {
	if ($runningJob->pid === null || !posix_kill($runningJob->pid, 0)) {
		saveAsAbandoned($runningJob);
	}
}

$queuedJobs = $jobRepo->findAll(status: UpdateJobStatus::QUEUED);

foreach ($queuedJobs as $queuedJob) {
    $now = new DateTimeImmutable();
    $minutesSinceCreation = ($now->getTimestamp() - $queuedJob->createdAt->getTimestamp()) / 60;
    if ($minutesSinceCreation >= 10) {
        saveAsAbandoned($queuedJob);
    }
}

function saveAsAbandoned(UpdateJob $job): void {
	global $jobRepo;
	$job->status = UpdateJobStatus::ABANDONED;
	$jobRepo->save($job);
	echo "Job {$job->id} (pid: {$job->pid}) marked as abandoned\n";
}