<?php

namespace App\Core;

class Logger {
	private const array LOG_PATHS = [
		'db' => BASE_PATH."/logs/db.log",
		'default' => BASE_PATH."/logs/default.log",
		'admin_update' => BASE_PATH."/logs/admin_update.log",
        'user_update' => BASE_PATH."/logs/user_update.log",
		'cron_update' => BASE_PATH."/logs/cron_update.log",
		'ddragon_update' => BASE_PATH."/logs/ddragon_update.log",
	];

	public static function log(string $type, string $message):void {
		if (!file_exists(BASE_PATH."/logs")) mkdir(BASE_PATH."/logs");
		$path = self::LOG_PATHS[$type] ?? self::LOG_PATHS['default'];
		$entry = "[".date("Y-m-d H:i:s")."]: ".$message."\n";

        $fileExists = file_exists($path);
		file_put_contents($path, $entry, FILE_APPEND);

        if (!$fileExists) {
            chmod($path, 0664);
        }
	}
}