<?php
include_once dirname(__DIR__,2)."/config/data.php";
include_once dirname(__DIR__,2)."/src/functions/fe-functions.php";

$dbcn = create_dbcn();

if ($dbcn -> connect_error)	exit("Database Connection failed");

$search = $_SERVER['HTTP_SEARCH'] ?? $_GET['search'] ?? NULL;
if ($search != NULL) {
    $search_results = search_all($dbcn, $search);
	echo json_encode($search_results);
    exit;
}

$dbcn->close();