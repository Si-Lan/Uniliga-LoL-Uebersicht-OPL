<?php
$root = __DIR__."/../../";
include_once $root."admin/functions/get-opl-data.php";

$type = $_SERVER["HTTP_TYPE"] ?? NULL;
if ($type == NULL) exit;

// adds the given tournament to DB (1 Call to OPL-API)
if ($type == "tournament") {
	$id = $_SERVER["HTTP_ID"] ?? NULL;
	if (strlen($id) == 0) {
		echo "";
		exit;
	}
	echo "<div class='turnier-get-result-content'>";
	echo "<div class='clear-button' onclick=\"clear_tourn_res_info()\">clear</div>";
	$result = get_tournament($id);
	echo $result["echo"];
	echo "</div>";
}