<?php

namespace App\Domain\Entities;

use App\Domain\Enums\Jobs\UpdateJobAction;
use App\Domain\Enums\Jobs\UpdateJobContextType;
use App\Domain\Enums\Jobs\UpdateJobStatus;
use App\Domain\Enums\Jobs\UpdateJobType;
use DateTimeImmutable;

class UpdateJob {
	public function __construct(
		public int $id,
		public UpdateJobType $type,
		public UpdateJobAction $action,
		public UpdateJobStatus $status,
		public float $progress,
		public ?UpdateJobContextType $contextType,
		public Tournament|Team|Matchup|null $context,
		public ?Tournament $tournament,
		public ?DateTimeImmutable $startedAt,
		public ?DateTimeImmutable $finishedAt,
		public ?string $message,
		public DateTimeImmutable $createdAt,
		public DateTimeImmutable $updatedAt,
		public ?int $pid
	) {}

	public function isFinished(): bool {
		return !($this->status === UpdateJobStatus::QUEUED || $this->status === UpdateJobStatus::RUNNING);
	}

	public function startJob(int $pid): void {
		$this->setStartedAtNow();
		$this->status = UpdateJobStatus::RUNNING;
		$this->progress = 0;
		$this->message = null;
		$this->pid = $pid;
	}
	public function finishJob(bool $success = true): void {
		$this->setFinishedAtNow();
		$this->status = $success ? UpdateJobStatus::SUCCESS : UpdateJobStatus::ERROR;
		$this->progress = 100;
	}

	public function cancelJob(): void {
		$this->setFinishedAtNow();
		$this->status = UpdateJobStatus::CANCELLED;
	}

	public function setStartedAtNow(): void {
		$this->startedAt = new DateTimeImmutable();
	}
	public function setFinishedAtNow(): void {
		$this->finishedAt = new DateTimeImmutable();
	}

	public function addMessage(string $message): void {
		$this->message = $this->message ?? "";
		$this->message .= "\n".$message;
	}
}