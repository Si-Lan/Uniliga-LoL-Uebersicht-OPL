<?php
$root = __DIR__."/../../";
include_once $root."setup/data.php";

$type = $_SERVER["HTTP_TYPE"] ?? NULL;
if ($type == NULL) exit;

$dbcn = create_dbcn();

if ($type == "add_split") {
	$season = $_SERVER["HTTP_SEASON"] ?? NULL;
	$split = $_SERVER["HTTP_SPLIT"] ?? NULL;
	$start = $_SERVER["HTTP_START"] ?? NULL;
	$end = ($_SERVER["HTTP_END"] ?? NULL) == '' ? NULL : $_SERVER["HTTP_END"];
	$dbcn->execute_query("INSERT INTO lol_ranked_splits (season, split, split_start, split_end) VALUES (?,?,?,?)",[$season,$split,$start,$end]);
}
if ($type == "remove_split") {
	$season = $_SERVER["HTTP_SEASON"] ?? NULL;
	$split = $_SERVER["HTTP_SPLIT"] ?? NULL;
	$dbcn->execute_query("DELETE FROM lol_ranked_splits WHERE season = ? AND split = ?", [$season,$split]);
}
if ($type == "update_split") {
	$season = $_SERVER["HTTP_SEASON"] ?? NULL;
	$split = $_SERVER["HTTP_SPLIT"] ?? NULL;
	$start = $_SERVER["HTTP_START"] ?? NULL;
	$end = ($_SERVER["HTTP_END"] ?? NULL) == '' ? NULL : $_SERVER["HTTP_END"];
	$dbcn->execute_query("UPDATE lol_ranked_splits SET split_start = ?, split_end = ? WHERE season = ? AND split = ?", [$start, $end, $season, $split]);
}