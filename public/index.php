<?php
include_once dirname(__DIR__)."/config/data.php";
include_once dirname(__DIR__)."/src/functions/fe-functions.php";

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
switch ($segments[0]??'') {
	case '':
		require 'pages/startpage.php';
		break;
	case 'turnier':
		$_GET["tournament"] = $segments[1] ?? null;
		if ($segments[2]??"" === 'team') $_GET["team"] = $segments[3] ?? null;
		if ($segments[2]??"" === 'gruppe') $_GET["group"] = $segments[3] ?? null;
		if ($segments[2]??"" === 'wildcard') $_GET["wildcard"] = $segments[3] ?? null;
		if ($segments[2]??"" === 'playoffs') $_GET["playoffs"] = $segments[3] ?? null;

		// Turnier-Seite - /turnier/123
		if (!isset($segments[2])) {
			require 'pages/tournament-details.php';
			break;
		}
        // Team-Liste - /turnier/123/teams
        if ($segments[2] === 'teams') {
			require 'pages/teams-list.php';
			break;
        }
		// Team-Liste - /turnier/123/elo
		if ($segments[2] === 'elo') {
			require 'pages/elo-overview.php';
			break;
		}
		// Gruppen-Seite - /turnier/123/gruppe/123
		if ($segments[2] === 'gruppe') {
			require 'pages/group-details.php';
			break;
		}
		// Wildcard-Seite - /turnier/123/wildcard/123
		if ($segments[2] === 'wildcard') {
			require 'pages/wildcard-details.php';
			break;
		}
		// Playoffs-Seite - /turnier/123/playoffs/123
		if ($segments[2] === 'playoffs') {
			require 'pages/playoffs-details.php';
			break;
		}
		// Turnier-Team-Seite - /turnier/123/team/123
		if ($segments[2] === 'team' && !isset($segments[4])) {
			require 'pages/teams-tournament-details.php';
			break;
		}
		// Turnier-Team-Matchhistory-Seite - /turnier/123/team/123/matchhistory
        if ($segments[2] === 'team' && $segments[4] === 'matchhistory') {
            require 'pages/teams-tournament-matchhistory.php';
            break;
        }
		// Turnier-Team-Stats-Seite - /turnier/123/team/123/stats
		if ($segments[2] === 'team' && $segments[4] === 'stats') {
			require 'pages/teams-tournament-statistics.php';
			break;
		}
        // Route nicht gefunden
		include404();
        break;

    case 'team':
		$_GET["team"] = $segments[1] ?? null;
		if ($segments[2]??"" === 'turnier') $_GET["tournament"] = $segments[3] ?? null;

		// Team-Seite - /team/123
		if (!isset($segments[2])) {
			require 'pages/team-details.php';
			break;
		}
		// Turnier-Team-Seite - /team/123/turnier/123
		if ($segments[2] === 'turnier' && !isset($segments[4])) {
			require 'pages/teams-tournament-details.php';
			break;
		}
		// Turnier-Team-Matchhistory-Seite - /team/123/turnier/123/matchhistory
		if ($segments[2] === 'turnier' && $segments[4] === 'matchhistory') {
			require 'pages/teams-tournament-matchhistory.php';
			break;
		}
		// Turnier-Team-Stats-Seite - /team/123/turnier/123/stats
		if ($segments[2] === 'turnier' && $segments[4] === 'stats') {
			require 'pages/teams-tournament-statistics.php';
			break;
		}
		// Route nicht gefunden
		include404();
        break;

    case 'spieler':
        $playerId = $segments[1] ?? null;
        // Spieler-Seite - /spieler/123
        if ($playerId) {
            $_GET["player"] = $playerId;
			require 'pages/player-details.php';
        }
        // Spieler-Suche - /spieler
        else {
			require 'pages/player-search.php';
		}
		break;

	default:
		include404();
		break;
}
function include404():void {
	$_GET["error"] = "404";
	http_response_code(404);
	require 'pages/error.php';
}

?>
</html>
