<?php
include_once __DIR__."/../setup/data.php";
include_once __DIR__."/../functions/fe-functions.php";

$dbcn = create_dbcn();

if ($dbcn -> connect_error)	exit("Database Connection failed");

$search = $_SERVER['HTTP_SEARCH'] ?? $_GET['search'] ?? NULL;
if ($search != NULL) {
    $search_results = search_all($dbcn, $search);
	echo json_encode($search_results);
    exit;
}

$dbcn->close();