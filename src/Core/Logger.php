<?php

namespace App\Core;

class Logger {
	private const array LOG_PATHS = [
		'db' => BASE_PATH."/logs/db.log",
		'default' => BASE_PATH."/logs/default.log"
	];

	public static function log(string $type, string $message):void {
		$path = self::LOG_PATHS[$type] ?? self::LOG_PATHS['default'];
		$entry = "[".date("Y-m-d H:i:s")."]: ".$message."\n";
		file_put_contents($path, $entry, FILE_APPEND);
	}
}