<?php
include __DIR__."/get-opl-data.php";
function create_tournament_buttons(mysqli $dbcn):string {
	$result = "
	<button class='refresh-button refresh-tournaments' onclick='create_tournament_buttons()'>
		Refresh
	</button>";

	$result.= "<div class='turnier-select-list'>";

	$tournaments = $dbcn->query("SELECT * FROM tournaments ORDER BY OPL_ID")->fetch_all(MYSQLI_ASSOC);
	$tournaments_by_id = array();
	foreach ($tournaments as $tournament) {
		$tournaments_by_id[$tournament["OPL_ID"]] = $tournament;
	}

	foreach ($tournaments as $tournament) {
		$result .= create_tournament_get_button($tournament);
	}

	$result .= "</div>";

	return $result;
}