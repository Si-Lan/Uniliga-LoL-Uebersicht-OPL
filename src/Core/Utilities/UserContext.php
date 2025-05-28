<?php

namespace App\Core\Utilities;

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
	public static function playerTablesExtended(): bool {
		return (isset($_COOKIE["preference_ptextended"]) && $_COOKIE["preference_ptextended"] === "1");
	}

	public static function isLoggedIn(): bool {
		if (isset($_COOKIE['admin-login'])) {
			if (password_verify($_ENV['ADMIN_PASS'], $_COOKIE['admin-login'])) {
				return true;
			}
		}
		return false;
	}
	public static function checkLoginParametersAndRedirect():bool {
		$password = $_ENV['ADMIN_PASS'];

		if (isset($_GET["login"])) {
			if (isset($_POST["keypass"])) {
				if ($_POST["keypass"] != $password) {
					return false;
				} else {
					setcookie('admin-login', password_hash($password, PASSWORD_BCRYPT),time()+31536000,'/');
					$pageurl = preg_replace('~([?&])login(=*)[^&]*~', '$1', $_SERVER['REQUEST_URI']);
					$pageurl = rtrim($pageurl, "?");
					header("Location: $pageurl");
				}
			}
		}

		if (isset($_GET["logout"])) {
			if (isset($_COOKIE['admin-login'])) {
				unset($_COOKIE['admin-login']);
				setcookie('admin-login','',time()-3600,'/');
			}
			if (isset($_COOKIE['admin_btns'])) {
				unset($_COOKIE['admin_btns']);
				setcookie('admin_btns','',time()-3600,'/');
			}
			$pageurl = preg_replace('~([?&])logout(=*)[^&]*~', '$1', $_SERVER['REQUEST_URI']);
			$pageurl = rtrim($pageurl, "?");
			header("Location: $pageurl");
		}

		return true;
	}

	public static function isMaintenanceMode(): bool {
		return file_exists(BASE_PATH.'/config/maintenance.enable');
	}
}