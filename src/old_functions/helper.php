<?php
function is_logged_in(): bool {
	include_once(dirname(__DIR__,2) . "/config/data.php");
	$admin_pass = get_admin_pass();
	if (isset($_COOKIE['admin-login'])) {
		if (password_verify($admin_pass, $_COOKIE['admin-login'])) {
			return TRUE;
		}
	}
	return FALSE;
}
function is_light_mode(bool $return_class_name = FALSE): bool|string {
	if (isset($_COOKIE['lightmode']) && $_COOKIE['lightmode'] === "1") {
		return $return_class_name ? "light" : TRUE;
	}
	return $return_class_name ? "" : FALSE;
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

	if ($current_tournament == NULL) {
		return NULL;
	}

	if ($current_tournament["eventType"] == "tournament") {
		return $current_tournament["OPL_ID"];
	} else {
		return $current_tournament["OPL_ID_top_parent"];
	}
}

function get_second_ranked_split_for_tournament(mysqli $dbcn, $tournament_id, $string=FALSE): array|string|null {
	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournament_id])->fetch_assoc();
	$split = $dbcn->execute_query("SELECT * FROM lol_ranked_splits WHERE season > ? OR (season = ? AND split > ?) ORDER BY season, split LIMIT 1",[$tournament["ranked_season"], $tournament["ranked_season"], $tournament["ranked_split"]])->fetch_assoc();
	if ($split == null) {
        return ($string) ? "" : null;
    }
    $split_string = "{$split['season']}-{$split['split']}";
	return ($string) ? $split_string : $split;
}

function get_current_ranked_split(mysqli $dbcn, $tournament_id) {
	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournament_id])->fetch_assoc();
	$ranked_season = $tournament["ranked_season"];
	$ranked_split = $tournament["ranked_split"];
	$ranked_season_comb = "$ranked_season-$ranked_split";

	if (!isset($_COOKIE["tournament_ranked_splits"])) {
		// Keine Split-Auswahl gespeichert, nehme ersten Split des Turniers
		$current_split = $ranked_season_comb;
	} else {
		$splits = json_decode($_COOKIE["tournament_ranked_splits"], true) ?? [];
		if (array_key_exists($tournament_id, $splits)) {
			$current_split = $splits[$tournament_id];
		} else {
			// Keine Split-Auswahl für aktuelles Turnier gespeichert, nehme ersten Split des Turniers
			$current_split = $ranked_season_comb;
		}
	}

	return $current_split;
}