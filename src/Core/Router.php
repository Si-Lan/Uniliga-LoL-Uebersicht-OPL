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
			'api' => self::handleApi('api', 'App\\API'),
			'apiAdmin' => self::handleApi('admin/api', 'App\\API\\Admin'),
			'apiOpl' => self::handleApi('admin/api/opl', 'App\\API\\Admin\\ImportOpl'),
			'apiRgapi' => self::handleApi('admin/api/rgapi', 'App\\API\\Admin\\ImportRgapi'),
			'ajax' => self::handleAjax('ajax', 'App\\Ajax'),
			'ajaxAdmin' => self::handleAjax('admin/ajax', 'App\\Ajax\\Admin'),
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

	private static function handleAjax(string $basePath, string $namespace):void {
		header('Content-Type: application/json');

		if (!self::authorizeAjaxApiRequest($basePath)) return;

		$requestPath = self::getRequestPath($basePath);

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

	private static function handleApi(string $basePath, string $namespace):void {
		header('Content-Type: application/json');

		if (!self::authorizeAjaxApiRequest($basePath)) return;

		$requestPath = self::getRequestPath($basePath);

		$segments = explode('/', $requestPath);
		$httpMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

		if (empty($segments[0])) {
			http_response_code(400);
			echo json_encode(['error' => 'Invalid route']);
			exit;
		}

		$resource = array_shift($segments);
		$resourceClass = "$namespace\\" . ucfirst($resource) . 'Handler';

		if(!class_exists($resourceClass)) {
			http_response_code(404);
			echo json_encode(['error' => 'Resource not found']);
			exit;
		}

		$handler = new $resourceClass();

		$method = strtolower($httpMethod) . ucfirst($resource);
		$methodParameters = [];
		$hasId = false;

		foreach ($segments as $segment) {
			if (is_numeric($segment)) {
				$methodParameters[] = (int) $segment;
				$hasId = true;
			} else {
				$method .= ucfirst($segment);
			}
		}
		if ($httpMethod === 'GET' && !$hasId) {
			$method .= 'All';
		}

		if (!method_exists($handler, $method)) {
			http_response_code(404);
			echo json_encode(['error' => "Method $method not found"]);
			exit;
		}

		$reflection = new \ReflectionMethod($handler, $method);
		$requiredParams = $reflection->getNumberOfRequiredParameters();
		$totalParams = $reflection->getNumberOfParameters();
		$givenParams = count($methodParameters);

		$expectedParameterString = ($requiredParams === $totalParams) ? $requiredParams : $requiredParams . '-' . $totalParams;

		if ($givenParams < $requiredParams || $givenParams > $totalParams) {
			http_response_code(400);
			echo json_encode([
				'error' => 'Invalid number of arguments',
				'expected' => "$expectedParameterString",
				'given' => $givenParams
			]);
			exit;
		}

		try {
			$handler->$method(...$methodParameters);
		} catch (\Throwable $e) {
			http_response_code(500);
			echo json_encode(['error' => $e->getMessage()]);
		}

		/* Mapping:
		 * GET /resources/{id} => getResources($id)
		 * GET /resources/{id}/subresource => getResourcesSubresourcesAll($id)
		 * GET /resources/{id}/subresource/{id} => getResourcesSubresources($id,$id)
		 * GET /resources => getResourcesAll()
		 * POST /resources => postResources()
		 */
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

	private static function authorizeAjaxApiRequest(string $basePath): bool {
		if (str_starts_with($basePath, 'admin') && !UserContext::isLoggedIn()) {
			http_response_code(403);
			echo json_encode(['error' => 'Invalid permissions']);
			return false;
		} else {
			return true;
		}
	}

	private static function getRequestPath($basePath): string {
		$requestPath = trim(parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH),'/');
		$requestPath = preg_replace("#^$basePath#", '', $requestPath);
		return trim($requestPath, '/');
	}
}