<?php

function autoload_App($class): void
{
	$namespace_prefix = 'App\\';

	// Prüfen, ob die Klasse mit dem Prefix beginnt
	if (strncmp($namespace_prefix, $class, strlen($namespace_prefix)) !== 0) {
		// Nicht unser Namespace, ignoriere
		return;
	}

	// Den Rest des Klassennamens holen
	$relative_class = substr($class, strlen($namespace_prefix));
	// Klasse in entsprechenden Pfad umwandeln (z.B. App\Controllers\HomeController -> src/Controllers/HomeController.php)
	$file = __DIR__.'/'.str_replace('\\', '/', $relative_class).'.php';

	// Wenn die Datei existiert, lade sie
	if (file_exists($file)) {
		require_once $file;
	}
}

spl_autoload_register('autoload_App');
