<?php
const BASE_PATH = __DIR__;

require_once BASE_PATH."/src/autoload.php";

require_once BASE_PATH."/src/dotenv.php";
try {
	loadEnv();
} catch (Exception $e) {
	header("HTTP/1.0 500 Internal Server Error");
	echo $e->getMessage();
	exit();
}