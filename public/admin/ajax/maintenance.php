<?php
include_once __DIR__.'/../../functions/helper.php';

$turn = $_SERVER["HTTP_TURN"] ?? null;

if (!is_logged_in()) die();

var_dump($turn);

if ($turn == "on") {
	$maintenance_file = fopen(__DIR__ . "/../../config/maintenance.enable","w");
	fclose($maintenance_file);
}
if ($turn == "off") {
	unlink(__DIR__ . "/../../config/maintenance.enable");
}