<?php
// public/ajax/index.php

require_once dirname(__DIR__,2). '/bootstrap.php';

header('Content-Type: application/json');

$basePath = '/ajax';
$request = parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH);
$request = preg_replace("#^$basePath#", '', $request);
$request = trim($request, '/');

$segments = explode('/', $request);
$controller = $segments[0] ?? null;
$method = $segments[1] ?? null;
$method = isset($segments[1]) ? str_replace('-','', lcfirst(ucwords($segments[1],'-'))) : null;

if (!$controller || !$method) {
	http_response_code(400);
	echo json_encode(['error' => 'Invalid route']);
	exit;
}

$controllerClass = 'App\\AjaxHandlers\\' . ucfirst($controller) . 'Handler';

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

if ($controller === "fragment") {
	header('Content-Type: text/html');
}


try {
	$handler->$method($_GET);
} catch (Exception $e) {
	http_response_code(500);
	echo json_encode(['error' => $e->getMessage()]);
}
