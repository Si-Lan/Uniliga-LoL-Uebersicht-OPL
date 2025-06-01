<?php
/** @var mysqli $dbcn  */

use App\UI\Components\Navigation\Header;
use App\UI\Enums\HeaderType;
use App\UI\Page\AssetManager;
use App\UI\Page\PageMeta;

$pageMeta = new PageMeta('Update Log', bodyClass: 'admin admin-logs');
AssetManager::addJsFile('/admin/scripts/main.js');

echo new Header(HeaderType::ADMIN_LOG);

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
?>

<h2 style='text-align: start'>Logs:</h2>

<?php
$error_pointer = 0;
foreach ($logs as $log) {
    ?>
    <a href='/cron-jobs/cron_logs/<?=$log?>'><?=$log?></a><br>
    <?php
    preg_match($logdate_regex, $log, $matches);
    if (array_key_exists($matches[3],$split_errors) && array_key_exists($matches[2],$split_errors[$matches[3]]) && array_key_exists($matches[1],$split_errors[$matches[3]][$matches[2]])) {
        foreach ($split_errors[$matches[3]][$matches[2]][$matches[1]] as $error) {
            ?>
            <a href='/cron-jobs/cron_logs/cron_errors/<?=$error?>'>----- <?=$error?></a><br>
            <?php
            unset($errors[array_search($error,$errors)]);
        }
    }
}

if (count($errors) > 0) {
    ?>
    <h2 style='text-align: start'>weitere Errors:</h2>
    <?php
    foreach ($errors as $error) {
        ?>
        <a href='/cron-jobs/cron_logs/cron_errors/<?=$error?>'><?=$error?></a><br>
        <?php
    }
}