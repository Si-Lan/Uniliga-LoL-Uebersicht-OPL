<?php
include __DIR__."/get-opl-data.php";

function create_ranked_split_list(mysqli $dbcn):string {
	$result = "<div class='ranked-split-list'>";

	$result .= create_ranked_split_rows($dbcn);

	$result .= "<div class='button-row'>
					<button type='button' class='add_ranked_split'><div class='material-symbol'>".file_get_contents(dirname(__DIR__,3)."/public/assets/icons/material/add.svg")."</div>Hinzufügen</button>
					<button type='button' class='save_ranked_split_changes'><div class='material-symbol'>".file_get_contents(dirname(__DIR__,3)."/public/assets/icons/material/save.svg")."</div>Änderungen Speichern</button>
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
						<button class='sec-button reset_inputs' title='Zurücksetzen'><div class='material-symbol'>".file_get_contents(dirname(__DIR__,3)."/public/assets/icons/material/restart.svg")."</div></button>
						<button class='sec-button delete_ranked_split' title='Löschen'><div class='material-symbol'>".file_get_contents(dirname(__DIR__,3)."/public/assets/icons/material/delete.svg")."</div></button>
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
						<button class='sec-button save_ranked_split' title='Zurücksetzen'><div class='material-symbol'>".file_get_contents(dirname(__DIR__,3)."/public/assets/icons/material/save.svg")."</div></button>
						<button class='sec-button delete_ranked_split' title='Löschen'><div class='material-symbol'>".file_get_contents(dirname(__DIR__,3)."/public/assets/icons/material/close.svg")."</div></button>
					</div>";
}

function to_assoc_subarrays(array $array, string $new_key) {
	$new_array = [];
	foreach ($array as $element) {
		$new_array[$element[$new_key]] = $element;
	}
	return $new_array;
}