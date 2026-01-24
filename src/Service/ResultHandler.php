<?php

namespace App\Service;

use App\Domain\Enums\SaveResult;
use App\Domain\ValueObjects\RepositorySaveResult;

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


	/**
	 * @param array<RepositorySaveResult> $saveResults
	 * @param string $entityType
	 */
	public function handleSaveResults(array $saveResults, string $entityType = 'entity'): void {
		$array = [
			SaveResult::UPDATED->value => 0,
			SaveResult::NOT_CHANGED->value => 0,
			SaveResult::FAILED->value => 0,
			SaveResult::INSERTED->value => 0,
		];
		foreach ($saveResults as $saveResult) {
			switch ($saveResult->result) {
				case SaveResult::UPDATED:
					$array[SaveResult::UPDATED->value]++;
					break;
				case SaveResult::NOT_CHANGED:
					$array[SaveResult::NOT_CHANGED->value]++;
					break;
				case SaveResult::FAILED:
					$array[SaveResult::FAILED->value]++;
					break;
				case SaveResult::INSERTED:
					$array[SaveResult::INSERTED->value]++;
					break;
			}
		}
		$this->jobHandler->addResultMessage(count($saveResults)." $entityType:");
		if ($array[SaveResult::INSERTED->value] !== 0) $this->jobHandler->addResultMessage("- ".$array[SaveResult::INSERTED->value]." zur Datenbank hinzugefügt");
		if ($array[SaveResult::NOT_CHANGED->value] !== 0) $this->jobHandler->addResultMessage("- ".$array[SaveResult::NOT_CHANGED->value]." unverändert");
		if ($array[SaveResult::UPDATED->value] !== 0) $this->jobHandler->addResultMessage("- ".$array[SaveResult::UPDATED->value]." aktualisiert");
		if ($array[SaveResult::FAILED->value] !== 0) $this->jobHandler->addResultMessage("- ".$array[SaveResult::FAILED->value]." Fehler");
	}

    public function handleWithCustomMessages(SaveResult $saveResult, array $messages): void {
        if (isset($messages[$saveResult->value])) {
            $this->jobHandler->addMessage($messages[$saveResult->value]);
        } else {
            $this->handleSaveResult($saveResult);
        }
    }
}