<?php

namespace App\API;

use JetBrains\PhpStorm\NoReturn;

abstract class AbstractHandler {
	#[NoReturn] protected function sendErrorResponse(int $statusCode, string $message): void {
		http_response_code($statusCode ?: 500);
		echo json_encode(['error' => $message]);
		exit;
	}
	protected function checkRequestMethod(string $method): void {
		if ($_SERVER['REQUEST_METHOD'] !== $method) {
			$this->sendErrorResponse(400, 'Invalid request method');
		}
	}
	protected function parseRequestData(): array {
		$requestData = json_decode(file_get_contents('php://input'), true);
		if (json_last_error() !== JSON_ERROR_NONE || !$requestData) {
			$this->sendErrorResponse(400, 'Invalid request data');
		}
		return $requestData;
	}

	protected function validateRequestData(array $data, array $requiredKeys): void {
		foreach ($requiredKeys as $key) {
			if (!array_key_exists($key, $data)) {
				$this->sendErrorResponse(400, "Missing Key '$key' in JSON");
			}
		}
	}
}