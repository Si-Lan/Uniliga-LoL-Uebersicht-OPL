<?php

$routes = [
	'' => BASE_PATH.'/public/pages/startpage.php',

	'turnier/{tournament}' => BASE_PATH.'/public/pages/tournament-details.php',
	'turnier/{tournament}/teams' => BASE_PATH.'/public/pages/teams-list.php',
	'turnier/{tournament}/elo' => BASE_PATH.'/public/pages/elo-overview.php',
	'turnier/{tournament}/gruppe/{event}' => BASE_PATH.'/public/pages/group-details.php',
	'turnier/{tournament}/wildcard/{event}' => BASE_PATH.'/public/pages/group-details.php',
	'turnier/{tournament}/playoffs/{event}' => BASE_PATH.'/public/pages/group-details.php',
	'turnier/{tournament}/team/{team}' => BASE_PATH.'/public/pages/teams-tournament-details.php',
	'turnier/{tournament}/team/{team}/matchhistory' => BASE_PATH.'/public/pages/teams-tournament-matchhistory.php',
	'turnier/{tournament}/team/{team}/stats' => BASE_PATH.'/public/pages/teams-tournament-statistics.php',

	'team/{team}' => BASE_PATH.'/public/pages/team-details.php',
	'team/{team}/turnier/{tournament}' => BASE_PATH.'/public/pages/teams-tournament-details.php',
	'team/{team}/turnier/{tournament}/matchhistory' => BASE_PATH.'/public/pages/teams-tournament-matchhistory.php',
	'team/{team}/turnier/{tournament}/stats' => BASE_PATH.'/public/pages/teams-tournament-statistics.php',

	'spieler' => BASE_PATH.'/public/pages/player-search.php',
	'spieler/{player}' => BASE_PATH.'/public/pages/player-details.php',
];
$adminRoutes = [
	'admin' => BASE_PATH.'/public/admin/pages/admin.php',
	'admin/rgapi' => BASE_PATH.'/public/admin/pages/rgapi.php',
	'admin/ddragon' => BASE_PATH.'/public/admin/pages/ddragon-updates.php',
	'admin/updates' => BASE_PATH.'/public/admin/pages/update-logs.php',
];


/**
 * @param string $path
 * @param array $routes
 * @return array{file: string, params: array}|null
 */
function matchRoute(string $path, array $routes): ?array {
	foreach ($routes as $routePattern => $file) {
		// erstellt aus dem route Pattern eine RegEx
		$regex = preg_replace('#\{[^/]+}#', '([^/]+)', $routePattern);
		$regex = "#^$regex$#";
		// prüft ob aufgerufener Pfad einem Pattern matcht, wobei die Parameter in den Platzhalter Segmenten in $matches gefangen werden
		if (preg_match($regex, $path, $matches)) {
			array_shift($matches);
			// holt die entsprechenden Namen der eingegebenen Parameter aus dem Route Pattern
			preg_match_all('/\{([^}]+)}/', $routePattern, $paramNames);
			$params = array_combine($paramNames[1], $matches);
			return ['file' => $file, 'params' => $params];
		}
	}
	return null;
}