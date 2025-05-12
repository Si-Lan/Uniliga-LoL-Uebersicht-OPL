<?php

namespace App\Utilities;

class UserPreferences {
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
}