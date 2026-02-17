<?php

namespace App\API\Admin\Events;

use App\API\Admin\Events\AbstractEventStreamHandler;
use App\Domain\Repositories\UpdateJobRepository;
use App\Service\LogViewer;

class JobsStreamHandler extends AbstractEventStreamHandler {
	public function __construct() {}

	public function handleEventStream() {
		$repo = new UpdateJobRepository();
		$logViewer = new LogViewer();

		$now = new \DateTimeImmutable();
		$oneMinuteAgo = $now->sub(new \DateInterval('PT1M'));

		// Send initial batch: jobs that started in the last minute
		$recentJobs = $repo->findStartedSince($oneMinuteAgo);
		$initial = [];
		foreach ($recentJobs as $job) {
			$details = $logViewer->getJobDetails($job->id);
			if ($details !== null) $initial[] = $details;
		}

		if (!empty($initial)) {
			$this->sendSSEMessageJson('initial', $initial);
		}

		// Track last seen update time
		$lastUpdate = $now;

		$this->streamLoop(2, function() use ($repo, $logViewer, &$lastUpdate) {
			// Fetch jobs updated since last check
			$updatedJobs = $repo->findUpdatedSince($lastUpdate);
			$changedJobs = [];
			foreach ($updatedJobs as $job) {
				$details = $logViewer->getJobDetails($job->id);
				if ($details !== null) $changedJobs[] = $details;
			}

			// Send updates
			if (!empty($changedJobs)) {
				foreach ($changedJobs as $cj) {
					$this->sendSSEMessageJson('update', $cj);
				}
			}

			$now = new \DateTimeImmutable();
			$lastUpdate = $now->sub(new \DateInterval('PT1S'));
		});
	}
}