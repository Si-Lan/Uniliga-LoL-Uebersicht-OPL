<?php
$root = __DIR__."/../../";
include_once $root."/setup/data.php";
include_once $root."/functions/fe-functions.php";

check_login();
?>
<!DOCTYPE html>
<html lang="de">
<?php

$dbcn = create_dbcn();
$loggedin = is_logged_in();
$lightmode = is_light_mode(true);

try {
	$dbcn = create_dbcn();
} catch (Exception $e) {
	echo create_html_head_elements(title: "Error");
	echo "<body class='$lightmode'>";
	echo create_header(title: "error");
	echo "<div style='text-align: center'>Database Connection failed</div></body>";
	exit();
}

echo create_html_head_elements(css: [""], js: ["admin"], title: "Update Log | Uniliga LoL - Übersicht" ,loggedin: $loggedin);

?>
<body class="admin admin-logs <?php echo $lightmode?>">
<?php

echo create_header($dbcn, title: "admin_update_log", open_login: !$loggedin);

$logdate_regex = "/(\d{2})_(\d{2})_(\d{2}).log/";
$comp_log_file_date = function($a,$b) use ($logdate_regex):int {
    preg_match($logdate_regex,$a, $a_matches);
    preg_match($logdate_regex,$b, $b_matches);

    if ($a_matches[3] != $b_matches[3]) {
        return ($a_matches[3] < $b_matches[3]) ? -1 : 1;
    }
	if ($a_matches[2] != $b_matches[2]) {
		return ($a_matches[2] < $b_matches[2]) ? -1 : 1;
	}
	if ($a_matches[1] != $b_matches[1]) {
		return ($a_matches[1] < $b_matches[1]) ? -1 : 1;
	}
    return 0;
};

if ($loggedin) {
	$dir = new DirectoryIterator(__DIR__."/../../cron-jobs/cron_logs");
	$logs = [];
	foreach ($dir as $file) {
		if ($file->isDot() || $file->isDir() || $file->getFilename() == ".gitignore" || $file->getFilename() == ".htaccess") {
			continue;
		}
		$logs[] = $file->getFilename();
	}
	$dir = new DirectoryIterator(__DIR__."/../../cron-jobs/cron_logs/cron_errors");
	$errors = [];
	foreach ($dir as $file) {
		if ($file->isDot() || $file->isDir() || $file->getFilename() == ".gitignore") {
			continue;
		}
		$errors[] = $file->getFilename();
	}

    usort($logs, $comp_log_file_date);
    usort($errors, $comp_log_file_date);
    $logs = array_reverse($logs);
    $errors = array_reverse($errors);

    $split_errors = [];
    foreach ($errors as $error) {
		preg_match($logdate_regex, $error, $matches);
		$split_errors[$matches[3]][$matches[2]][$matches[1]][] = $error;
	}

	echo "<h2 style='text-align: start'>Logs:</h2>";
	$error_pointer = 0;
	foreach ($logs as $log) {
		echo "<a href='/cron-jobs/cron_logs/".$log."'>".$log."</a><br>";
		preg_match($logdate_regex, $log, $matches);
        if (array_key_exists($matches[3],$split_errors) && array_key_exists($matches[2],$split_errors[$matches[3]]) && array_key_exists($matches[1],$split_errors[$matches[3]][$matches[2]])) {
            foreach ($split_errors[$matches[3]][$matches[2]][$matches[1]] as $error) {
				echo "<a href='/cron-jobs/cron_logs/cron_errors/".$error."'>----- ".$error."</a><br>";
                unset($errors[array_search($error,$errors)]);
			}
		}
	}

    if (count($errors) > 0) {
		echo "<h2 style='text-align: start'>weitere Errors:</h2>";
		foreach ($errors as $error) {
			echo "<a href='/cron-jobs/cron_logs/cron_errors/".$error."'>".$error."</a><br>";
		}
	}
}
?>
</body>
</html>

