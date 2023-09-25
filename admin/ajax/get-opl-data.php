<?php
$root = __DIR__."/../../";
include_once $root."admin/functions/get-opl-data.php";

$type = $_SERVER["HTTP_TYPE"] ?? NULL;
if ($type == NULL) exit;

// gets the given tournament from OPL (1 Call to OPL-API)
if ($type == "get_tournament") {
	$id = $_SERVER["HTTP_ID"] ?? NULL;
	if (strlen($id) == 0) {
		echo "{}";
		exit;
	}
	$result = get_tournament($id);
	echo json_encode($result);
}

// adds the given tournament to DB
if ($type == "write_tournament") {
	$data = $_SERVER["HTTP_DATA"] ?? NULL;
	$data = json_decode($data,true);
	$result = write_tournament($data);
	echo $result;
}