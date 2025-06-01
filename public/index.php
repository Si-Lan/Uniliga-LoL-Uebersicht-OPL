<?php

require_once dirname(__DIR__).'/bootstrap.php';

include_once BASE_PATH."/config/data.php";

\App\Core\Router::handle('pages');