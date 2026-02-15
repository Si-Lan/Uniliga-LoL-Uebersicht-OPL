<?php

namespace App\Ajax;

use App\UI\Page\AssetManager;
use JetBrains\PhpStorm\NoReturn;

class AbstractFragmentHandler {
	protected function sendJsonFragment(string $html): void {
		echo json_encode([
			'html' => $html,
			'js' => AssetManager::getJsModules(),
			'css' => AssetManager::getCssFiles()
		]);
	}
	#[NoReturn]
	protected function sendJsonError(string $message, int $code): void {
		http_response_code($code);
		echo json_encode(['error'=>$message]);
		exit;
	}
}