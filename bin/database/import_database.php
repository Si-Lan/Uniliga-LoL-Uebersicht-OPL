<?php
require_once dirname(__DIR__,2) . '/bootstrap.php';

$options = getopt('', ['file:', 'database:']);
$sqlFile = $options['file'] ?? null;
$dbName = $options['database'] ?? 'uniliga_opl_overview';

if (!$sqlFile) {
    echo "Fehler: Keine SQL-Datei angegeben. Bitte mit --file=dateiname.sql aufrufen. SQL-Datei sollte im 'database' Verzeichnis liegen.\n";
    echo "Verwendung: php import_database.php --file=dateiname.sql [--database=datenbankname]\n";
    exit(1);
}

$filePath = BASE_PATH . "/database/$sqlFile";
if (!file_exists($filePath)) {
    echo "Fehler: SQL-Datei '$filePath' nicht gefunden.\n";
    exit(1);
}

echo "Importiere Daten aus '$sqlFile' in Datenbank '$dbName'...\n";

// Datenbankverbindung herstellen
$dbcn = \App\Core\DatabaseConnection::getConnection();

// Datenbank auswählen
$dbcn->select_db($dbName);

$errors = 0;
$total = 0;
$current = 0;

try {
    // Foreign Key Constraints deaktivieren
    $dbcn->query("SET FOREIGN_KEY_CHECKS=0");

    // Datei einlesen
    $sqlContent = file_get_contents($filePath);

    // Statements parsen
	$statements = splitInsertStatements($sqlContent);

    $total = count($statements);
    $current = 0;
    $barLength = 50;

	echo "Es wurden $total INSERT-Statements gefunden.\n";

    foreach ($statements as $statement) {
        $current++;
        $progress = $current / $total;
        $progressBar = floor($progress * $barLength);
        
        echo sprintf("\r[%-{$barLength}s] %d%%", str_repeat('#', $progressBar), $progress * 100);
        
        // SQL-Statement ausführen
        if (!$dbcn->query($statement)) {
            echo "\nFehler beim Ausführen eines INSERT-Statements: " . $dbcn->error . "\n";
            $errors++;
        }
    }
} catch (\Exception $e) {
    echo "\nEin unerwarteter Fehler ist aufgetreten: " . $e->getMessage() . "\n";
    $errors++;
} finally {
    // Foreign Key Constraints in jedem Fall wieder aktivieren
    $dbcn->query("SET FOREIGN_KEY_CHECKS=1");
    
    // Abschlussmeldung ausgeben
    if ($errors > 0) {
        echo "\nImport abgeschlossen mit $errors Fehler(n).\n";
    } else {
        echo "\nDaten erfolgreich in '$dbName' importiert ($current von $total Statements).\n";
    }
}



function splitInsertStatements(string $sql): array {
	$statements = [];
	$current = '';
	$inString = false;
	$stringChar = '';

	$length = strlen($sql);
	$barLength = 50;
	$lastProgress = 0;

	for ($i = 0; $i < $length; $i++) {
		$char = $sql[$i];
		$current .= $char;

		// Wenn wir innerhalb eines Strings sind
		if ($inString) {
			if ($char === $stringChar) {
				// Prüfen, ob es ein escaptes Quote ist (z.B. \' oder "")
				$backslashes = 0;
				$j = $i - 1;
				while ($j >= 0 && $sql[$j] === '\\') {
					$backslashes++;
					$j--;
				}
				if ($backslashes % 2 === 0) {
					// echtes Ende des Strings
					$inString = false;
					$stringChar = '';
				}
			}
			continue;
		}

		// Wenn wir in keinen String sind, aber ein Quote finden
		if ($char === '\'' || $char === '"') {
			$inString = true;
			$stringChar = $char;
			continue;
		}

		// Semikolon = Ende eines Statements (außerhalb von Strings)
		if ($char === ';') {
			$trimmed = trim($current);
			if ($trimmed !== '') {
				$statements[] = $trimmed;
			}
			$current = '';
		}

		// Fortschritt berechnen und ausgeben
		$progress = intdiv($i * 100, $length); // Fortschritt als Prozent
		if ($progress > $lastProgress) {
			$lastProgress = $progress;
			$progressBar = floor(($progress / 100) * $barLength);
			echo sprintf("\r[%-{$barLength}s] %d%% Analysiert", str_repeat('#', $progressBar), $progress);
		}

	}

	// Falls am Ende noch was übrig bleibt
	$trimmed = trim($current);
	if ($trimmed !== '') {
		$statements[] = $trimmed;
	}

	// Fortschritt abschließen
	echo "\r[", str_repeat('#', $barLength), "] 100% Analysiert\n";

	return $statements;
}