<?php

namespace App\API\Admin;

use App\API\AbstractHandler;

class MaintenanceHandler extends AbstractHandler{
	public function putMaintenanceOn(): void {
		$maintenanceFile = fopen(BASE_PATH."/config/maintenance.enable","w");
		fclose($maintenanceFile);
	}
	public function putMaintenanceOff(): void {
		if (file_exists(BASE_PATH."/config/maintenance.enable")) {
			unlink(BASE_PATH."/config/maintenance.enable");
		}
	}
}