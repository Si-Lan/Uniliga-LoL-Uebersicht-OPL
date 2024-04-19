<?php
include __DIR__."/get-opl-data.php";
function create_tournament_buttons(mysqli $dbcn):string {
	$result = "
	<button class='refresh-button refresh-tournaments' onclick='create_tournament_buttons()'>
		Refresh
	</button>";

	$result.= "<div class='turnier-select-list'>";

	$events = $dbcn->query("SELECT * FROM tournaments")->fetch_all(MYSQLI_ASSOC);
	$tournaments = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType='tournament' ORDER BY season DESC, split DESC")->fetch_all(MYSQLI_ASSOC);
	$events = array_filter($events, function($element) use($tournaments) {return !in_array($element,$tournaments);});

	foreach ($tournaments as $tournament) {
		$result .= "<span class='tsl-heading'><h3>{$tournament["name"]}</h3><button class='toggle-turnierselect-accordeon' type='button' data-id='{$tournament["OPL_ID"]}'><div class='material-symbol'>".file_get_contents(__DIR__."/../../icons/material/expand_more.svg")."</div></button></span>";
		$result .= "<div class='turnier-sl-accordeon {$tournament["OPL_ID"]}'><div class='tsl-acc-content'>";
		$result .= create_tournament_get_button($tournament);

		$leagues = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType='league' AND OPL_ID_parent = ? ORDER BY number",[$tournament["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
		$events = array_filter($events, function($element) use($leagues) {return !in_array($element,$leagues);});

		if ($leagues != null) $result .= "<span class='tsl-heading'><h4>- Ligen</h4></span>";
		foreach ($leagues as $league) {
			$result .= "<span class='tsl-heading'><h5>Liga {$league["number"]}</h5><button class='toggle-turnierselect-accordeon' type='button' data-id='{$league["OPL_ID"]}'><div class='material-symbol'>".file_get_contents(__DIR__."/../../icons/material/expand_more.svg")."</div></button></span>";
			$result .= "<div class='turnier-sl-accordeon {$league["OPL_ID"]}'><div class='tsl-acc-content'>";
			$result .= create_tournament_get_button($league);
			$groups = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType='group' AND OPL_ID_parent = ? ORDER BY number",[$league["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
			$events = array_filter($events, function($element) use($groups) {return !in_array($element,$groups);});

			foreach ($groups as $group) {
				$result .= create_tournament_get_button($group);
			}
			$result .= "</div></div>"; //close league accordeon
		}

		$playoffs = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType='playoffs' AND OPL_ID_parent = ? ORDER BY number, numberRangeTo",[$tournament["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
		$events = array_filter($events, function($element) use($playoffs) {return !in_array($element,$playoffs);});

		if ($playoffs != null) $result .= "<span class='tsl-heading'><h4>-Playoffs</h4><button class='toggle-turnierselect-accordeon' type='button' data-id='playoffs'><div class='material-symbol'>".file_get_contents(__DIR__."/../../icons/material/expand_more.svg")."</div></button></span>";
		$result .= "<div class='turnier-sl-accordeon playoffs'><div class='tsl-acc-content'>";
		foreach ($playoffs as $playoff) {
			$result .= create_tournament_get_button($playoff);
		}
		$result .= "</div></div>"; //close playoff accordeon
		$result .= "</div></div>"; //close tournament accordeon
	}

	$events = array_values($events);
	if (count($events)>0) $result .= "<h3>nicht zugewiesene Turniere</h3>";
	foreach ($events as $event) {
		$result .= create_tournament_get_button($event);
	}
	$result .= "</div>";

	return $result;
}

function to_assoc_subarrays(array $array, string $new_key) {
	$new_array = [];
	foreach ($array as $element) {
		$new_array[$element[$new_key]] = $element;
	}
	return $new_array;
}