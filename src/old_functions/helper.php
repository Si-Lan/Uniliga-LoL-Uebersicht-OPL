<?php

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