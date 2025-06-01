<?php

require_once dirname(__DIR__).'/bootstrap.php';

include_once BASE_PATH."/config/data.php";
include_once BASE_PATH."/src/old_functions/fe-functions.php";

\App\Core\Router::handle('pages');