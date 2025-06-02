<?php

namespace App\Core;

use App\Core\Utilities\UserContext;
use App\Domain\Enums\EventType;
use App\Domain\Repositories\PlayerRepository;
use App\Domain\Repositories\TeamRepository;
use App\Domain\Repositories\TournamentRepository;
use App\UI\Page\LayoutRenderer;
use App\UI\Page\PageMeta;

class Router {
	public static function handle(string $context): void {
		match ($context) {
			'pages' => self::handlePages(),
			'api' => self::handleApiAjax('api', 'App\\API'),
			'apiAdmin' => self::handleApiAjax('admin/api', 'App\\API\\Admin'),
			'oplImport' => self::handleApiAjax('admin/api/import/opl', 'App\\API\\Admin\\ImportOpl'),
			'rgapiImport' => self::handleApiAjax('admin/api/import/rgapi', 'App\\API\\Admin\\ImportRgapi'),
			'ajax' => self::handleApiAjax('ajax', 'App\\Ajax'),
			'ajaxAdmin' => self::handleApiAjax('admin/ajax', 'App\\Ajax\\Admin'),
			default => self::trigger404()
		};
	}

	private static function handlePages(): void {
		UserContext::checkLoginParametersAndRedirect();
		if (UserContext::isMaintenanceMode() && !UserContext::isLoggedIn()) {
			http_response_code(503);
			self::renderPage(BASE_PATH.'/public/pages/maintenance.php');
			exit();
		}
		try {
			$dbcn = DatabaseConnection::getConnection();
		} catch (\Exception $e) {
			$_GET["error"] = "db";
			self::renderPage(BASE_PATH."/public/pages/error.php");
			exit();
		}

		$requestPath = trim(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH),'/');

		/** @var array<string,string> $routes */
		/** @var array<string,string> $adminRoutes */
		require_once BASE_PATH."/config/routes.php";

		$routeMatch = matchRoute($requestPath, $routes);
		$adminRouteMatch = matchRoute($requestPath, $adminRoutes);
		if (!$routeMatch && !$adminRouteMatch) self::trigger404();

		if ($adminRouteMatch) {
			if (!UserContext::isLoggedIn()) {
				self::renderPage(BASE_PATH.'/public/admin/pages/admin-empty.php');
			} else {
				self::renderPage($adminRouteMatch['file']);
			}
			return;
		}

		$_GET = array_merge($_GET, $routeMatch['params']);

		self::validateRoutingParams($_GET);

		self::renderPage($routeMatch['file']);
	}

	private static function handleApiAjax(string $basePath, string $namespace) {
		header('Content-Type: application/json');

		if (str_starts_with($basePath, 'admin') && !UserContext::isLoggedIn()) {
			http_response_code(403);
			echo json_encode(['error' => 'Invalid permissions']);
		}

		$requestPath = trim(parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH),'/');
		$requestPath = preg_replace("#^$basePath#", '', $requestPath);
		$requestPath = trim($requestPath, '/');

		$segments = explode('/', $requestPath);
		$controller = $segments[0] ?? null;
		$method = isset($segments[1]) ? str_replace('-','', lcfirst(ucwords($segments[1],'-'))) : null;

		if (!$controller || !$method) {
			http_response_code(400);
			echo json_encode(['error' => 'Invalid route']);
			exit;
		}

		$controllerClass = "$namespace\\" . ucfirst($controller) . 'Handler';

		if (!class_exists($controllerClass)) {
			http_response_code(404);
			echo json_encode(['error' => 'Controller not found']);
			exit;
		}

		$handler = new $controllerClass();

		if (!method_exists($handler, $method)) {
			http_response_code(404);
			echo json_encode(['error' => 'Method not found']);
			exit;
		}

		try {
			$handler->$method($_GET);
		} catch (\Throwable $e) {
			http_response_code(500);
			echo json_encode(['error' => $e->getMessage()]);
		}
	}

	private static function validateRoutingParams(array &$params): array {
		$tournamentRepo = new TournamentRepository();
		$teamRepo = new TeamRepository();
		$playerRepo = new PlayerRepository();

		self::validateIntId($params, 'tournament', fn($id)=>$tournamentRepo->tournamentExists($id, EventType::TOURNAMENT));
		self::validateIntId($params, 'group', fn($id)=>$tournamentRepo->standingEventExists($id));
		self::validateIntId($params, 'wildcard', fn($id)=>$tournamentRepo->tournamentExists($id, EventType::WILDCARD));
		self::validateIntId($params, 'playoffs', fn($id)=>$tournamentRepo->tournamentExists($id, EventType::PLAYOFFS));
		self::validateIntId($params, 'team', fn($id)=>$teamRepo->teamExists($id));
		self::validateIntId($params, 'player', fn($id)=>$playerRepo->playerExists($id));

		return $params;
	}
	private static function validateIntId(array &$params, string $param, callable $existsCallback): void {
		if (!isset($params[$param])) {
			return;
		}
		$params[$param] = filter_var($params[$param], FILTER_VALIDATE_INT);

		if (!$params[$param] || !$existsCallback($params[$param])) {
			self::trigger404($param);
		}
	}

	private static function renderPage(string $pageFile): void {
		$dbcn = DatabaseConnection::getConnection(); //TODO: temporär, solange pages noch selbst Datenbank Anfragen machen
		ob_start();
		require $pageFile;
		$pageContent = ob_get_clean();
		if (!isset($pageMeta) || !$pageMeta instanceof PageMeta) {
			$pageMeta = new PageMeta();
		}
		LayoutRenderer::render($pageMeta, $pageContent);
	}
	public static function trigger404(string $type = ''):void {
		$_GET["error"] = "404";
		$_GET["404type"] = $type;
		self::renderPage(BASE_PATH.'/public/pages/error.php');
		exit();
	}
}