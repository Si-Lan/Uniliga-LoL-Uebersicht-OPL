<?php
function is_logged_in(): bool {
	include_once(dirname(__FILE__) . "/../setup/data.php");
	$admin_pass = get_admin_pass();
	if (isset($_COOKIE['admin-login'])) {
		if (password_verify($admin_pass, $_COOKIE['admin-login'])) {
			return TRUE;
		}
	}
	return FALSE;
}
function logged_in_buttons_hidden(): bool {
	if (isset($_COOKIE['admin_btns']) && $_COOKIE['admin_btns'] === "0") {
		return TRUE;
	}
	return FALSE;
}
function is_light_mode(bool $return_class_name = FALSE): bool|string {
	if (isset($_COOKIE['lightmode']) && $_COOKIE['lightmode'] === "1") {
		return $return_class_name ? "light" : TRUE;
	}
	return $return_class_name ? "" : FALSE;
}
