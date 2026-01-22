<?php

namespace App\Domain\Entities;

use App\Core\Utilities\DateTimeHelper;
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
		public Tournament|Team|Matchup|Patch|null $context,
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
		if ($this->message !== "") $this->message .= "\n";
		$this->message .= $message;
	}

	public function getLastUpdateTime(): ?DateTimeImmutable {
		return $this->finishedAt ?? $this->updatedAt;
	}
    public function getLastUpdateString(): string {
        $latestTime = $this->getLastUpdateTime();
		return DateTimeHelper::getRelativeTimeString($latestTime);
    }
    public function getNextUpdateTryString(): string {
        $userUpdateIntervalSeconds = 600;
        $lastUpdate = $this->finishedAt;
        if ($lastUpdate == null) {
			if ($userUpdateIntervalSeconds >= 60) {
				return "in ". round($userUpdateIntervalSeconds/60) ." Minuten";
			} else {
				return "in {$userUpdateIntervalSeconds} Sekunden";
			}
        }
        $currentTime = new DateTimeImmutable();
        $diff = $currentTime->diff($lastUpdate, true);
		$remainingSeconds = $userUpdateIntervalSeconds - ($diff->i*60+$diff->s);
		if ($remainingSeconds < 0) {
			return "jetzt";
		}
        if ($remainingSeconds < 60) {
            return "in {$remainingSeconds} Sekunden";
        }
		return $remainingSeconds === 60 ? "in 1 Minute" : "in ". round($remainingSeconds/60) ." Minuten";
    }

    public function getApiOutput(): array {
		$output = get_object_vars($this);
		$output['lastUpdate'] = $this->getLastUpdateString();
		$output['nextTry'] = $this->getNextUpdateTryString();
        return $output;
    }
}