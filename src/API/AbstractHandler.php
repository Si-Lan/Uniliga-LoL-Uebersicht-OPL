<?php

namespace App\API;

use JetBrains\PhpStorm\NoReturn;

abstract class AbstractHandler {
	#[NoReturn] protected function sendErrorResponse(int $statusCode, string $message): void {
		http_response_code($statusCode);
		echo json_encode(['error' => $message]);
		exit;
	}
	protected function checkRequestMethod(string $method): void {
		if ($_SERVER['REQUEST_METHOD'] !== $method) {
			$this->sendErrorResponse(400, 'Invalid request method');
		}
	}
}