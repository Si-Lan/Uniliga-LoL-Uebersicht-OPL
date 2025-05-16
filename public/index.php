<?php

use App\Database\DatabaseConnection;
use App\Enums\EventType;
use App\Page\LayoutRenderer;
use App\Page\PageMeta;
use App\Repositories\PlayerRepository;
use App\Repositories\TeamRepository;
use App\Repositories\TournamentRepository;
use App\Utilities\UserContext;

require_once dirname(__DIR__).'/bootstrap.php';

/** @var array<string,string> $routes */
require_once BASE_PATH."/config/routes.php";
include_once BASE_PATH."/config/data.php";
include_once BASE_PATH."/src/functions/fe-functions.php";

check_login();

if (UserContext::isMaintenanceMode() && !UserContext::isLoggedIn()) {
	http_response_code(503);
	renderPage(BASE_PATH.'/public/pages/maintenance.php');
	exit();
}

try {
    $dbcn = DatabaseConnection::getConnection();
} catch (Exception $e) {
    $_GET["error"] = "db";
	$pageFile = BASE_PATH."/public/pages/error.php";
	renderPage($pageFile);
	exit();
}

$requestPath = trim(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH),'/');
$routeMatch = matchRoute($requestPath, $routes);

if (!$routeMatch) trigger404();

$_GET = array_merge($_GET, $routeMatch['params']);

if (isset($_GET['tournament']) && !(new TournamentRepository())->tournamentExists($_GET['tournament'], EventType::TOURNAMENT)) {
    trigger404('tournament');
}
if (isset($_GET['group']) && !(new TournamentRepository())->tournamentExists($_GET['group'], EventType::GROUP)) {
	trigger404('group');
}
if (isset($_GET['wildcard']) && !(new TournamentRepository())->tournamentExists($_GET['wildcard'], EventType::WILDCARD)) {
	trigger404('wildcard');
}
if (isset($_GET['playoffs']) && !(new TournamentRepository())->tournamentExists($_GET['playoffs'], EventType::PLAYOFFS)) {
	trigger404('playoffs');
}
if (isset($_GET['team']) && !(new TeamRepository())->teamExists($_GET['team'])) {
	trigger404('team');
}
if (isset($_GET['player']) && !(new PlayerRepository())->playerExists($_GET['player'])) {
	trigger404('player');
}

renderPage($routeMatch['file']);

function trigger404(string $type = ''):void {
	$_GET["error"] = "404";
	$_GET["404type"] = $type;
    $pageFile = BASE_PATH.'/public/pages/error.php';
    renderPage($pageFile);
	exit();
}

function renderPage(string $pageFile): void {
	$dbcn = DatabaseConnection::getConnection(); // TODO: entfernen (Workaround, solange noch Seitenelemente mit direkten DB-Zugriffen arbeiten)
	ob_start();
	require $pageFile;
	$pageContent = ob_get_clean();
	if (!isset($pageMeta) || !$pageMeta instanceof PageMeta) {
		$pageMeta = new PageMeta();
	}
	LayoutRenderer::render($pageMeta, $pageContent);
}
