<?php

require_once dirname(__DIR__,2)."/bootstrap.php";

include_once dirname(__DIR__,2)."/config/data.php";
include_once dirname(__DIR__,2)."/src/old_functions/fe-functions.php";
include_once dirname(__DIR__,2)."/src/old_functions/helper.php";
include_once dirname(__DIR__,2)."/src/old_functions/admin/fe-functions.php";
include_once dirname(__DIR__,2)."/src/old_functions/admin/ddragon-update.php";

\App\Core\Router::handle('pages');
