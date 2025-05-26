<?php
include_once dirname(__DIR__,3)."/src/old_functions/helper.php";

$turn = $_SERVER["HTTP_TURN"] ?? null;

if (!is_logged_in()) die();

var_dump($turn);

if ($turn == "on") {
	$maintenance_file = fopen(dirname(__DIR__,3) . "/config/maintenance.enable","w");
	fclose($maintenance_file);
}
if ($turn == "off") {
	unlink(dirname(__DIR__,3) . "/config/maintenance.enable");
}