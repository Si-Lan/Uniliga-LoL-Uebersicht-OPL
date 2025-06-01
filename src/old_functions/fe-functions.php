<?php

function create_dropdown(string $type, array $items):string {
	$first_key = array_key_first($items);
	$result = "<div class='button-dropdown-wrapper'>";
	$result .= "<button type='button' class='button-dropdown' data-dropdowntype='$type'>{$items[$first_key]}<span class='material-symbol'>".file_get_contents(dirname(__DIR__,2)."/public/assets/icons/material/expand_more.svg")."</span></button>";
	$result .= "<div class='dropdown-selection'>";
	foreach ($items as $data_name=>$name) {
		$selected = ($data_name == $first_key) ? "selected-item" : "";
		$result .= "<button type='button' class='dropdown-selection-item $selected' data-selection='$data_name'>$name</button>";
	}
	$result .= "</div>";
	$result .=  "</div>"; // button-dropdown-wrapper
	return $result;
}