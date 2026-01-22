<?php

namespace App\Service;

use App\Core\Enums\LogType;
use App\Core\Logger;
use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Entities\Patch;
use App\Domain\Entities\Tournament;
use App\Domain\Entities\UpdateJob;
use App\Domain\Enums\Jobs\UpdateJobAction;
use App\Domain\Enums\Jobs\UpdateJobStatus;
use App\Domain\Enums\Jobs\UpdateJobType;
use App\Domain\Repositories\UpdateJobRepository;

class JobHandler {
	use DataParsingHelpers;
	protected UpdateJobRepository $jobRepo;
	public Logger $logger;
    public ?UpdateJob $job;
    public ?Tournament $tournamentContext;
	public ?Patch $patchContext;
    protected UpdateJobType $updateType;
    /** @var UpdateJobAction[] */
    protected array $expectedActions;

    public function __construct(UpdateJobType $updateType, UpdateJobAction ...$expectedActions) {
        $this->updateType = $updateType;
        $this->expectedActions = $expectedActions;
        
        $logType = $this->determineLogType();
        $this->logger = new Logger($logType);
        $this->jobRepo = new UpdateJobRepository();
        
        $options = getopt('j:');
        $jobId = $options['j'] ?? null;

		$jobId = $this->intOrNull($jobId);
        
        $this->initializeJob($jobId);
    }

    private function determineLogType(): LogType {
		if ($this->expectedActions[0]->isDdragonDownload()) {
			return LogType::DDRAGON_UPDATE;
		}

        return match($this->updateType) {
            UpdateJobType::ADMIN => LogType::ADMIN_UPDATE,
            UpdateJobType::USER => LogType::USER_UPDATE,
            UpdateJobType::CRON => LogType::CRON_UPDATE,
        };
    }

	private function validateAndSetContext(): void {
		$context = $this->job->context;

		if ($this->job->action->isDdragonDownload()) {
			if (!($context instanceof Patch)) {
				$this->logger->warning("Job {$this->job->id} has invalid context");
				echo "Job {$this->job->id} has invalid context\n";
				exit(1);
			}
			$this->patchContext = $context;
		} elseif ($this->job->type === UpdateJobType::ADMIN) {
			if ($context !== null && !($context instanceof Tournament)) {
				$this->logger->warning("Job {$this->job->id} has invalid context");
				echo "Job {$this->job->id} has invalid context\n";
				exit(1);
			}
			$this->tournamentContext = $context;
		}
	}

    private function initializeJob(?int $jobId): void {
        match($this->updateType) {
            UpdateJobType::ADMIN => $this->initializeAdminUpdateJob($jobId),
            UpdateJobType::USER => $this->initializeUserUpdateJob($jobId),
            UpdateJobType::CRON => $this->initializeCronUpdateJob($jobId),
        };
    }

    private function initializeAdminUpdateJob(?int $jobId): void {
        if ($jobId === null) {
            echo "No job id given, use -j <jobid>\n";
            exit(1);
        }

        $this->job = $this->jobRepo->findById($jobId);
        if ($this->job === null) {
            $this->logger->warning("Job $jobId not found");
            echo "Job $jobId not found\n";
            exit(1);
        }

        if ($this->job->status !== UpdateJobStatus::QUEUED) {
            $this->logger->warning("Job $jobId is not queued");
            echo "Job $jobId is not queued\n";
            exit(1);
        }

        if (!in_array($this->job->action, $this->expectedActions, true)) {
            $expectedActionValues = implode(', ', array_map(fn(UpdateJobAction $action) => $action->value, $this->expectedActions));
            $this->logger->warning("Job $jobId is not one of the expected actions: [$expectedActionValues]");
            echo "Job $jobId is not one of the expected actions: [$expectedActionValues]\n";
            exit(1);
        }

		$this->validateAndSetContext();

        $this->startJob();
        $this->logger->info("Starting job $jobId");
    }

    private function initializeUserUpdateJob(?int $jobId): void {
        // Placeholder for user update job
    }

    private function initializeCronUpdateJob(?int $jobId): void {
        // Placeholder for cron update job
    }

    public function run(callable $executeCallback): void {
        try {
            call_user_func($executeCallback, $this);
            $this->finish();
            $this->logger->info("Finished job {$this->job->id}");
        } catch (\Exception $e) {
            $this->addMessage("Fatal error: " . $e->getMessage());
            $this->finish();
            $this->logger->error("Job {$this->job->id} failed: " . $e->getMessage(), $e);
        }
    }

    
    public function startJob(): void {
        $this->job->startJob(getmypid());
        $this->jobRepo->save($this->job);
    }

    public function finish(): void {
        $this->job->finishJob();
        $this->jobRepo->save($this->job);
    }

    public function addMessage(string $message): void {
        $this->job->addMessage($message);
        $this->jobRepo->save($this->job);
    }
	public function addResultMessage(string $result): void {
		$this->job->addResultMessage($result);
		$this->jobRepo->save($this->job);
	}
	public function addMessageAndResult(string $message): void {
		$this->job->addMessage($message);
		$this->job->addResultMessage($message);
		$this->jobRepo->save($this->job);
	}

    public function setProgress(float $percent): void {
        $this->job->progress = $percent;
        $this->jobRepo->save($this->job);
    }
}