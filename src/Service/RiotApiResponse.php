<?php

namespace App\Service;

class RiotApiResponse {

    public function __construct(
        private ?array $data,
        private ?int $statusCode = null,
        private ?string $error = null,
        private array $headers = [],
        private ?\Throwable $exception = null
    ) {}

    public static function success(?array $data, ?int $statusCode = 200, array $headers = []): self {
        return new self($data, $statusCode, null, $headers, null);
    }
    public static function error(string $error, ?int $statusCode = null, array $headers = [], ?\Throwable $exception = null): self {
        return new self(null, $statusCode, $error, $headers, $exception);
    }

    public function isSuccess(): bool {
        return $this->statusCode !== null
            && $this->statusCode >= 200
            && $this->statusCode < 300
            && $this->error === null;
    }

    public function getStatusCode(): ?int {
        return $this->statusCode;
    }
    public function getData(): array {
        return $this->data;
    }
    public function getError(): ?string {
        return $this->error;
    }
    public function getHeaders(): array {
        return $this->headers;
    }
    public function getException(): ?\Throwable {
        return $this->exception;
    }

    public function isRateLimitExceeded(): bool {
        return $this->statusCode === 429;
    }
}