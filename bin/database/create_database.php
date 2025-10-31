<?php
require_once dirname(__DIR__,2) . '/bootstrap.php';

$options = getopt('', ['name:','schema:']);
$dbName = $options['name'] ?? 'uniliga_opl_overview';
$schema = $options['schema'] ?? 'schema.sql';

$_ENV['DB_DATABASE'] = '';
$dbcn = \App\Core\DatabaseConnection::getConnection();

if (!$dbcn->query("CREATE DATABASE IF NOT EXISTS `$dbName` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci")) {
	echo "Fehler beim Erstellen der Datenbank: " . $dbcn->error . "\n";
	exit(1);
}

echo "Datenbank '$dbName' erstellt oder existiert bereits.\n";

$dbcn->select_db($dbName);

$schemaFile = BASE_PATH . "/database/$schema";
if (!file_exists($schemaFile)) {
	echo "Schema-Datei '$schemaFile' nicht gefunden.\n";
	exit(1);
}

$ddl = file_get_contents($schemaFile);

// Kommentare entfernen
$ddl = preg_replace('/--.*?(\r?\n|$)/', '', $ddl);

$statements = array_filter(array_map('trim', explode(';', $ddl)));

$total = count($statements);
$current = 0;
$barLength = 50;

foreach ($statements as $statement) {
	if ($statement === '') continue;

	$current++;
	$progress = $current / $total;
	$progressBar = floor($progress * $barLength);

	echo sprintf("\r[%-{$barLength}s] %d%%", str_repeat('#', $progressBar), $progress * 100);

	if (!$dbcn->query($statement)) {
		echo "\nFehler beim Ausführen eines Statements:\n$statement\n";
		echo "Fehler: " . $dbcn->error . "\n";
		exit(1);
	}
}

echo "\nDatenbank '$dbName' erfolgreich erstellt.\n";