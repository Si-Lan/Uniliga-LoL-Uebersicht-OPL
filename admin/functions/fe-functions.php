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

function create_ranked_split_list(mysqli $dbcn):string {
	$result = "<div class='ranked-split-list'>";

	$result .= create_ranked_split_rows($dbcn);

	$result .= "<div class='button-row'>
					<button type='button' class='add_ranked_split'><div class='material-symbol'>".file_get_contents(dirname(__FILE__)."/../../icons/material/add.svg")."</div>Hinzufügen</button>
					<button type='button' class='save_ranked_split_changes'><div class='material-symbol'>".file_get_contents(dirname(__FILE__)."/../../icons/material/save.svg")."</div>Änderungen Speichern</button>
				</div>";

	$result .= "</div>";
	return $result;
}
function create_ranked_split_rows(mysqli $dbcn):string {
	$splits = $dbcn->execute_query("SELECT * FROM lol_ranked_splits")->fetch_all(MYSQLI_ASSOC);
	$result = "";
	foreach ($splits as $split) {
		$result .= "<div class='ranked-split-edit'>
						<label class=\"write_ranked_split_season\">Season<input type=\"text\" value=\"{$split["season"]}\" readonly></label>
						<label class=\"write_ranked_split_split\">Split<input type=\"text\" value=\"{$split["split"]}\" readonly></label>
						<label class=\"write_ranked_split_startdate\">Start<input type=\"date\" value=\"{$split["split_start"]}\"></label>
						<label class=\"write_ranked_split_enddate\">Ende<input type=\"date\" value=\"{$split["split_end"]}\"></label>
						<button class='sec-button reset_inputs'><div class='material-symbol'>".file_get_contents(dirname(__FILE__)."/../../icons/material/restart.svg")."</div></button>
						<button class='sec-button delete_ranked_split'><div class='material-symbol'>".file_get_contents(dirname(__FILE__)."/../../icons/material/delete.svg")."</div></button>
					</div>";
	}
	return $result;
}
function create_ranked_split_addition() {
	return "<div class='ranked-split-edit ranked_split_write'>
						<label class=\"write_ranked_split_season\">Season<input type=\"text\"></label>
						<label class=\"write_ranked_split_split\">Split<input type=\"text\"></label>
						<label class=\"write_ranked_split_startdate\">Start<input type=\"date\"></label>
						<label class=\"write_ranked_split_enddate\">Ende<input type=\"date\"></label>
						<button class='sec-button save_ranked_split'><div class='material-symbol'>".file_get_contents(dirname(__FILE__)."/../../icons/material/save.svg")."</div></button>
						<button class='sec-button delete_ranked_split'><div class='material-symbol'>".file_get_contents(dirname(__FILE__)."/../../icons/material/close.svg")."</div></button>
					</div>";
}

function to_assoc_subarrays(array $array, string $new_key) {
	$new_array = [];
	foreach ($array as $element) {
		$new_array[$element[$new_key]] = $element;
	}
	return $new_array;
}