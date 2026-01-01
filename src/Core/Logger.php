<?php

namespace App\Core;

use App\Core\Enums\LogType;

class Logger {
	private LogType $type;

	public function __construct(LogType $type = LogType::DEFAULT) {
		$this->type = $type;
	}

	public function debug(string $message): void {
		$this->writeLog('DEBUG', $message);
	}

	public function info(string $message): void {
		$this->writeLog('INFO', $message);
	}

	public function warning(string $message): void {
		$this->writeLog('WARNING', $message);
	}

	public function error(string $message): void {
		$this->writeLog('ERROR', $message);
	}

	public static function debugStatic(LogType $type, string $message): void {
		$logger = new self($type);
		$logger->debug($message);
	}

	public static function infoStatic(LogType $type, string $message): void {
		$logger = new self($type);
		$logger->info($message);
	}

	public static function warningStatic(LogType $type, string $message): void {
		$logger = new self($type);
		$logger->warning($message);
	}

	public static function errorStatic(LogType $type, string $message): void {
		$logger = new self($type);
		$logger->error($message);
	}

	private function writeLog(string $level, string $message): void {
		if (!file_exists(BASE_PATH."/logs")) mkdir(BASE_PATH."/logs");
		$typeKey = $this->type->value;
		$path = BASE_PATH . "/logs/{$typeKey}.log";
		$entry = "[" . date("Y-m-d H:i:s") . "] {$level} [{$typeKey}]: {$message}\n";

        $fileExists = file_exists($path);
		file_put_contents($path, $entry, FILE_APPEND);

        if (!$fileExists) {
            chmod($path, 0664);
        }
	}
}