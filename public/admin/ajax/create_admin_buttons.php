<?php
include_once dirname(__FILE__) . "/../../config/data.php";
include_once dirname(__FILE__)."/../functions/fe-functions.php";
$dbcn = create_dbcn();

$open_accordeons = $_SERVER["HTTP_OPEN_ACCORDEONS"] ?? "[]";
if ($open_accordeons == "") $open_accordeons = "[]";
$open_accordeons = json_decode($open_accordeons);
echo create_tournament_buttons($dbcn, $open_accordeons);