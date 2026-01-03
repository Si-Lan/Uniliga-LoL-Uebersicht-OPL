<?php

namespace App\Domain\ValueObjects;

use App\Domain\Enums\SaveResult;

class RepositorySaveResult implements \JsonSerializable {
    public function __construct(
        public SaveResult $result,
        public ?array $changes = null,
        public ?array $previous = null,
        public mixed $entity = null,
		public array $additionalData = []
    ) {}

    public function isSuccessful(): bool {
        return $this->result === SaveResult::INSERTED || $this->result === SaveResult::UPDATED;
    }

    public function isFailed(): bool {
        return $this->result === SaveResult::FAILED;
    }

    public function isNotChanged(): bool {
        return $this->result === SaveResult::NOT_CHANGED;
    }

    public function jsonSerialize(): array {
        return [
            'result' => $this->result->value,
            'changes' => $this->changes,
            'previous' => $this->previous,
            'entity' => $this->entity,
			'additionalData' => $this->additionalData
        ];
    }
}