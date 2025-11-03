<?php

namespace App\API;

use App\Domain\Repositories\UpdateJobRepository;

class JobsHandler extends AbstractHandler {
	private UpdateJobRepository $jobRepo;
	public function __construct() {
		$this->jobRepo = new UpdateJobRepository();
	}

	public function getJobs(int $jobId): void {
		$job = $this->jobRepo->findById($jobId);
		if ($job === null) {
			$this->sendErrorResponse(404, 'Job not found');
		}
		echo json_encode($job);
	}
}