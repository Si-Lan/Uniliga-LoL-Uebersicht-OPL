<?php

use App\Core\DatabaseConnection;
use App\Core\Utilities\UserContext;
use App\Domain\Enums\EventType;
use App\Domain\Repositories\PlayerRepository;
use App\Domain\Repositories\TeamRepository;
use App\Domain\Repositories\TournamentRepository;
use App\UI\Page\LayoutRenderer;
use App\UI\Page\PageMeta;

require_once dirname(__DIR__).'/bootstrap.php';

/** @var array<string,string> $routes */
require_once BASE_PATH."/config/routes.php";
include_once BASE_PATH."/config/data.php";
include_once BASE_PATH."/src/old_functions/fe-functions.php";

UserContext::checkLoginParametersAndRedirect();

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
		if ($param === 'group' && checkGroupIdforAlternative($_GET[$param])) {
			return;
		}
		trigger404($param);
	}
}
function checkGroupIdforAlternative($id):bool {
	global $tournamentRepo;
	return ($tournamentRepo->tournamentExists($id, EventType::WILDCARD) || $tournamentRepo->tournamentExists($id, EventType::PLAYOFFS));
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
	ob_start();
	require $pageFile;
	$pageContent = ob_get_clean();
	if (!isset($pageMeta) || !$pageMeta instanceof PageMeta) {
		$pageMeta = new PageMeta();
	}
	LayoutRenderer::render($pageMeta, $pageContent);
}
