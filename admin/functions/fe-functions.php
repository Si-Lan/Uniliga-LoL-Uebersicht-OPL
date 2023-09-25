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
		/*
		$result .= "<div class='turnier-button-wrap'>";
		$result .= "<div class=\"admin-turnier-button {$tournament["OPL_ID"]}\">
                        <span><a href='https://www.opleague.pro/event/{$tournament["OPL_ID"]}/' target='_blank'>{$tournament["name"]}</a></span>
                        <div class='admin-turnier-content'>";
        $result .= "<div>ID: {$tournament["OPL_ID"]}</div>";
		if ($tournament["OPL_ID_parent"] != NULL) {
			$result .= "<div>Parent: {$tournament["OPL_ID_parent"]} - {$tournaments_by_id[$tournament["OPL_ID_parent"]]["name"]}</div>";
		} else {
			$result .= "<div>Parent: </div>";
		}
		$result .= "<div>Split: {$tournament["split"]}</div>";
        $result .= "<div>Season: {$tournament["season"]}</div>";
		$result .= "<div>Typ: {$tournament["eventType"]}</div>";
        $result .= "<div>format: {$tournament["format"]}</div>";
		$result .= "<div>number: {$tournament["number"]}</div>";
        $result .= "<div>numberTo: {$tournament["numberRangeTo"]}</div>";
		$result .= "<div>Startdatum: {$tournament["dateStart"]}</div>";
        $result .= "<div>Enddatum: {$tournament["dateEnd"]}</div>";
		$result .= "<div>beendet: {$tournament["finished"]}</div>";
		$result .= "</div>
                    </div>";


		//$result .= "<div class='all-get-result no-res get-result {$tournament["OPL_ID"]}'></div>";
		$result .= "</div>";
		*/
	}

	$result .= "</div>";

	return $result;
}