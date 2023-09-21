<?php
include_once dirname(__FILE__)."/../../setup/data.php";
include_once dirname(__FILE__)."/../functions/fe-functions.php";
$dbcn = create_dbcn();

echo create_tournament_buttons($dbcn);