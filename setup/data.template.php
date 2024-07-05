<?php
function get_admin_pass():string {
	return "";
}

function create_dbcn():mysqli {
	$dbservername = "";
	$dbdatabase = "";
	$dbusername = "";
	$dbpassword = "";
	$dbport = NULL;
	$conn = new mysqli($dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport);
	$conn->set_charset("utf8");
	return $conn;
}

function get_rgapi_key():string {
	return "";
}

function get_opl_bearer_token():string {
	return "";
}