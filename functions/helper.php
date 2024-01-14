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
function admin_buttons_visible(bool $return_class_name = FALSE): bool|string {
	if (is_logged_in() && isset($_COOKIE['admin_btns']) && $_COOKIE['admin_btns'] === "1") {
		return $return_class_name ? "admin_li" : TRUE;
	}
	return $return_class_name ? "" : FALSE;
}
function is_light_mode(bool $return_class_name = FALSE): bool|string {
	if (isset($_COOKIE['lightmode']) && $_COOKIE['lightmode'] === "1") {
		return $return_class_name ? "light" : TRUE;
	}
	return $return_class_name ? "" : FALSE;
}

function summonercards_collapsed(): bool {
	if (isset($_COOKIE["preference_sccollapsed"]) && $_COOKIE["preference_sccollapsed"] === "1") {
		return TRUE;
	} else {
		return FALSE;
	}
}
function playertables_extended(): bool {
	if (isset($_COOKIE["preference_ptextended"]) && $_COOKIE["preference_ptextended"] === "0") {
		return FALSE;
	} else {
		return TRUE;
	}
}

function max_time_from_timestamp($timestamp):string {
	$days = floor($timestamp/86400);
	if ($days == 0) {
		if ($timestamp < 30) return "vor ein paar Sekunden";
		if ($timestamp < 60) return "vor $timestamp Sekunden";
		if ($timestamp < 120) return "vor 1 Minute";
		if ($timestamp < 3600) return "vor ".floor($timestamp/60)." Minuten";
		if ($timestamp < 7200) return "vor 1 Stunde";
		if ($timestamp < 86400) return "vor ".floor($timestamp/3600)." Stunden";
	}
	if ($days == 1) return "Gestern";
	if ($days < 7) return "vor $days Tagen";
	if ($days < 31) return "vor ".ceil($days/7)." Wochen";
	if ($days < 60) return "letzten Monat";

	$years = intval(date("Y",$timestamp)) - 1970;
	$months = intval(date("m",$timestamp)) - 1;
	if ($years > 0) {
		if ($years == 1) {
			return "letztes Jahr";
		} else {
			return "vor $years Jahren";
		}
	}
	if ($months > 0) {
		if ($months == 1) {
			return "letzen Monat";
		} else {
			return "vor $months Monate";
		}
	}
	return "unbekannt";
}

function get_top_parent_tournament(mysqli $dbcn, $event_id) {
	$current_tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$event_id])->fetch_assoc();

	if ($current_tournament["eventType"] == "tournament") {
		return $current_tournament["OPL_ID"];
	} elseif ($current_tournament["eventType"] == "league" || $current_tournament["eventType"] == "playoffs") {
		return $current_tournament["OPL_ID_parent"];
	} elseif ($current_tournament["eventType"] == "group") {
		return $dbcn->execute_query("SELECT OPL_ID_parent FROM tournaments WHERE eventType='league' AND OPL_ID = ?", [$current_tournament["OPL_ID_parent"]])->fetch_column();
	} else {
		return NULL;
	}
}