<?php
/**
 * @throws Exception
 */
function loadEnv(): void {
	$file = dirname(__DIR__) . "/.env";

	if (!file_exists($file)) {
		throw new \Exception("Environment-Config not found");
	}

	$envVars = parse_ini_file($file);
	if ($envVars === false) {
		throw new \Exception("Error reading Environment-Config");
	}

	foreach ($envVars as $key => $value) {
		$_ENV[$key] = $value;
	}
}