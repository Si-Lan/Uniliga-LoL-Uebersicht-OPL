<?php

use App\Components\Navigation\Header;
use App\Enums\HeaderType;
use App\Page\PageMeta;

$errortype = $_GET["error"] ?? NULL;

switch($errortype) {
	case "404":
        $type404 = $_GET["404type"] ?? null;
		http_response_code(404);
		$headerType = HeaderType::NOT_FOUND;
        switch ($type404) {
            case "tournament":
				$errortext = "Kein Turnier unter der angegebenen ID gefunden!";
				$title = "404 - Turnier nicht gefunden";
				break;
			case "group":
				$errortext = "Keine Gruppe unter der angegebenen ID gefunden!";
				$title = "404 - Gruppe nicht gefunden";
				break;
			case "wildcard":
				$errortext = "Kein Wildcard-Turnier unter der angegebenen ID gefunden!";
				$title = "404 - Wildcard-Turnier nicht gefunden";
				break;
			case "playoffs":
				$errortext = "Keine Playoffs unter der angegebenen ID gefunden!";
				$title = "404 - Playoffs nicht gefunden";
				break;
			case "team":
				$errortext = "Kein Team unter der angegebenen ID gefunden!";
				$title = "404 - Team nicht gefunden";
				break;
            case "team-in-tournament":
                $errortext = "Dieses Team spielt nicht im angegebenen Turnier";
				$title = "404 - Team nicht im Turnier";
                break;
			case "player":
                $errortext = "Kein Spieler unter der angegebenen ID gefunden!";
				$title = "404 - Spieler nicht gefunden";
                break;
			default:
				$errortext = "Die gesuchte Seite wurde nicht gefunden";
				$title = "404 - Seite nicht gefunden";
                break;
		}
		break;
    case "db":
        http_response_code(500);
        $headerType = HeaderType::ERROR;
        $errortext = "Fehler bei der Datenbankverbindung!";
		$title = "Fehler";
        break;
	default:
		$headerType = HeaderType::ERROR;
		$errortext = "Ein unbekannter Fehler ist aufgetreten";
		$title = "Fehler";
        break;
}

$pageMeta = new PageMeta($title, bodyClass: 'error');

?>

<?= new Header($headerType)?>
<div style='text-align: center'>
    <?=$errortext?>
</div>