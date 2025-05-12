<?php
require_once dirname(__DIR__,2)."/src/functions/fe-functions.php";

$errortype = $_GET["error"] ?? NULL;

switch($errortype) {
	case "404":
        $type404 = $_GET["404type"] ?? null;
		http_response_code(404);
        switch ($type404) {
            case "tournament":
				$errortext = "Kein Turnier unter der angegebenen ID gefunden!";
				echo create_html_head_elements(title: "404 - Turnier nicht gefunden | Uniliga LoL - Übersicht");
				break;
			case "group":
				$errortext = "Keine Gruppe unter der angegebenen ID gefunden!";
				echo create_html_head_elements(title: "404 - Gruppe nicht gefunden | Uniliga LoL - Übersicht");
				break;
			case "wildcard":
				$errortext = "Kein Wildcard-Turnier unter der angegebenen ID gefunden!";
				echo create_html_head_elements(title: "404 - Wildcard-Turnier nicht gefunden | Uniliga LoL - Übersicht");
				break;
			case "playoffs":
				$errortext = "Keine Playoffs unter der angegebenen ID gefunden!";
				echo create_html_head_elements(title: "404 - Playoffs nicht gefunden | Uniliga LoL - Übersicht");
				break;
			case "team":
				$errortext = "Kein Team unter der angegebenen ID gefunden!";
				echo create_html_head_elements(title: "404 - Team nicht gefunden | Uniliga LoL - Übersicht");
				break;
			case "player":
                $errortext = "Kein Spieler unter der angegebenen ID gefunden!";
				echo create_html_head_elements(title: "404 - Spieler nicht gefunden | Uniliga LoL - Übersicht");
                break;
			default:
				$errortext = "Die gesuchte Seite wurde nicht gefunden";
				echo create_html_head_elements(title: "404 - Seite nicht gefunden | Uniliga LoL - Übersicht");
                break;
		}
		break;
    case "db":
        http_response_code(500);
        $errortype = "error";
        $errortext = "Fehler bei der Datenbankverbindung!";
		echo create_html_head_elements(title: "Fehler | Uniliga LoL - Übersicht");
        break;
	default:
		$errortype = "error";
		$errortext = "Ein unbekannter Fehler ist aufgetreten";
		echo create_html_head_elements(title: "Fehler | Uniliga LoL - Übersicht");
        break;
}

?>
<body class="error <?=is_light_mode(true)?>">
    <?=create_header(title: $errortype)?>
    <div style='text-align: center'>
        <?=$errortext?>
    </div>
</body>