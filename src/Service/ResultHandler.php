<?php

namespace App\Service;

use App\Domain\Enums\SaveResult;

class ResultHandler {
    private JobHandler $jobHandler;

    public function __construct(JobHandler $jobHandler) {
        $this->jobHandler = $jobHandler;
    }

    public function handleSaveResult(SaveResult $saveResult, string $entityType = 'entity'): void {
        switch ($saveResult) {
            case SaveResult::UPDATED:
                $this->jobHandler->addMessage("Updated $entityType");
                break;
            case SaveResult::NOT_CHANGED:
                $this->jobHandler->addMessage("$entityType not changed");
                break;
            case SaveResult::FAILED:
                $this->jobHandler->addMessage("Failed to update $entityType");
                break;
            case SaveResult::INSERTED:
                $this->jobHandler->addMessage("Inserted $entityType");
                break;
        }
    }

    public function handleWithCustomMessages(SaveResult $saveResult, array $messages): void {
        if (isset($messages[$saveResult->value])) {
            $this->jobHandler->addMessage($messages[$saveResult->value]);
        } else {
            $this->handleSaveResult($saveResult);
        }
    }
}