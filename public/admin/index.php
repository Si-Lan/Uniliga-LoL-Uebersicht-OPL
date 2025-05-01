<?php

include_once dirname(__DIR__,2)."/config/data.php";
include_once dirname(__DIR__,2)."/src/functions/fe-functions.php";
include_once dirname(__DIR__,2)."/src/functions/helper.php";
include_once dirname(__DIR__,2)."/src/admin/functions/fe-functions.php";
include_once dirname(__DIR__,2)."/src/admin/functions/ddragon-update.php";

check_login();
?>
<!DOCTYPE html>
<html lang="de">
<?php

try {
	$dbcn = create_dbcn();
} catch (Exception $e) {
	echo create_html_head_elements(title: "Error");
	echo "<body class='".is_light_mode(true)."'>";
	echo create_header(title: "error");
	echo "<div style='text-align: center'>Database Connection failed</div></body>";
	exit();
}

$request = parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
$request = trim($request, '/');

$segments = explode('/', $request);

// Routing-Logik
switch ($segments[1]??'') {
	case '':
		require 'pages/admin.php';
		break;
	case 'rgapi':
		require 'pages/rgapi.php';
		break;
	case 'ddragon':
		require 'pages/ddragon-updates.php';
		break;
	case 'updates':
		require 'pages/update-logs.php';
		break;
	default:
		$_GET["error"] = "404";
		http_response_code(404);
		require dirname(__DIR__).'/pages/error.php';
		break;
}

?>
</html>
