<?php
include dirname(__DIR__,2) . '/bootstrap.php';

use App\Domain\Entities\UpdateJob;
use App\Domain\Enums\Jobs\UpdateJobStatus;
use App\Domain\Repositories\UpdateJobRepository;

$jobRepo = new UpdateJobRepository();

$oldJobsWithMessage = $jobRepo->findOldJobsWithMessage();

foreach ($oldJobsWithMessage as $job) {
	$job->message = null;
	$jobRepo->save($job);
	echo "Cleared message for job {$job->id}\n";
}

// Alte Log-Dateien löschen (älter als 2 Wochen)
$logsPath = BASE_PATH . "/logs/jobs";
$twoWeeksAgo = time() - (14 * 24 * 60 * 60);

if (is_dir($logsPath)) {
    $directories = new RecursiveDirectoryIterator($logsPath);
    $files = new RecursiveIteratorIterator($directories);
    
    foreach ($files as $file) {
        if ($file->isFile() && filemtime($file->getRealPath()) < $twoWeeksAgo) {
            unlink($file->getRealPath());
            echo "Deleted old (>2 weeks) log file: {$file->getFilename()}\n";
        }
    }
}