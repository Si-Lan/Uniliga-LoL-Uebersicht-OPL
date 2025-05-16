<?php

namespace App\Utilities;

class UserContext {
	public static function isLightMode():bool {
		return (isset($_COOKIE['lightmode']) && $_COOKIE['lightmode'] === "1");
	}
	public static function getLightModeClass():string {
		if (self::isLightMode()) {
			return "light";
		} else {
			return "";
		}
	}

	public static function summonerCardCollapsed(): bool {
		return (isset($_COOKIE['preference_sccollapsed']) && $_COOKIE['preference_sccollapsed'] === '1');
	}

	public static function isLoggedIn(): bool {
		if (isset($_COOKIE['admin-login'])) {
			if (password_verify($_ENV['ADMIN_PASS'], $_COOKIE['admin-login'])) {
				return true;
			}
		}
		return false;
	}

	public static function isMaintenanceMode(): bool {
		return file_exists(BASE_PATH.'/config/maintenance.enable');
	}
}