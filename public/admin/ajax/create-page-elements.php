<?php
$root = __DIR__."/../../";
include_once $root."admin/functions/fe-functions.php";
include_once $root . "config/data.php";

$type = $_SERVER["HTTP_TYPE"] ?? NULL;
if ($type == NULL) exit;

$dbcn = create_dbcn();

if ($type == "ranked_split_rows") {
	echo create_ranked_split_rows($dbcn);
	exit;
}