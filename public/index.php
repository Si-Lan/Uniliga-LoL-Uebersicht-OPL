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

$tournamentRepo = new TournamentRepository();
$teamRepo = new TeamRepository();
$playerRepo = new PlayerRepository();

function validateIntId(string $param, callable $existsCallback): void {
	if (!isset($_GET[$param])) {
		return;
	}

	$_GET[$param] = filter_var($_GET[$param], FILTER_VALIDATE_INT);

	if (!$_GET[$param] || !$existsCallback($_GET[$param])) {
		trigger404($param);
	}
}

validateIntId('tournament', fn($id)=>($tournamentRepo->tournamentExists($id, EventType::TOURNAMENT)));
validateIntId('group', fn($id)=>($tournamentRepo->tournamentExists($id, EventType::GROUP)));
validateIntId('wildcard', fn($id)=>($tournamentRepo->tournamentExists($id, EventType::WILDCARD)));
validateIntId('playoffs', fn($id)=>($tournamentRepo->tournamentExists($id, EventType::PLAYOFFS)));
validateIntId('team', fn($id)=>($teamRepo->teamExists($id)));
validateIntId('player', fn($id)=>($playerRepo->playerExists($id)));

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
