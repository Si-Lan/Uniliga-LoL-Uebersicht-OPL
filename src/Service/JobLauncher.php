<?php

namespace App\Service;

use App\Domain\Entities\UpdateJob;
use App\Domain\Enums\Jobs\UpdateJobType;

class JobLauncher {
	public static function launch(UpdateJob $job, string $options=""): void {
		$optionsString = "-j $job->id";
		if ($options !== "") {
			$optionsString .= " $options";
		}
		exec("php ".BASE_PATH."/bin/".self::script_dir($job)."/".self::script_name($job).".php $optionsString > ".self::log_path($job)." 2>&1 &");
	}

	private static function script_dir(UpdateJob $job): string {
		if ($job->action->isDdragonDownload()) { // weil ddragon downloads noch in eigenem Verzeichnis liegen
			return "ddragon_updates";
		}
		return match ($job->type) {
			UpdateJobType::ADMIN => "admin_updates",
			UpdateJobType::USER => "user_updates",
			UpdateJobType::CRON => "cron_updates",
		};
	}

	private static function script_name(UpdateJob $job): string {
		if ($job->type === UpdateJobType::USER) { // weil user update jobs aktuell so heißen, dass user_ davor steht
			return "user_".$job->action->value;
		}
		if ($job->action->isDdragonDownload()) { // weil ddragon downloads alle über eine Datei laufen
			return "download_patch_imgs";
		}
		return $job->action->value;
	}

	private static function log_path(UpdateJob $job): string {
		$logpath = BASE_PATH."/logs/jobs/".self::script_dir($job);
		if (!file_exists($logpath)) mkdir($logpath, recursive: true);
		return $logpath."/".self::script_name($job)."-".$job->id."_".date("Y-m-d_H-i").".log";
	}
}