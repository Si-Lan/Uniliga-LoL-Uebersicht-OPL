<?php
require_once dirname(__DIR__,2) . '/bootstrap.php';

use App\Core\Enums\LogType;
use App\Core\Logger;
use App\Domain\Entities\Tournament;
use App\Domain\Enums\Jobs\UpdateJobAction;
use App\Domain\Enums\Jobs\UpdateJobStatus;
use App\Domain\Enums\SaveResult;
use App\Domain\Repositories\GameRepository;
use App\Domain\Repositories\UpdateJobRepository;
use App\Service\Updater\GameUpdater;

$logger = new Logger(LogType::ADMIN_UPDATE);

$options = getopt('j:');
$jobId = $options['j'] ?? null;
if ($jobId === null) {
	echo "No job id given, use -j <jobid>";
	exit;
}

$jobRepo = new UpdateJobRepository();
$job = $jobRepo->findById($jobId);

if ($job === null) {
    $logger->warning("Job $jobId not found");
    echo "Job $jobId not found\n";
    exit;
}
if ($job->status !== UpdateJobStatus::QUEUED) {
	$logger->warning("Job $jobId is not queued");
    echo "Job $jobId is not queued\n";
    exit;
}
if ($job->action !== UpdateJobAction::UPDATE_GAMEDATA) {
	$logger->warning("Job $jobId is not an update gamedata job");
    echo "Job $jobId is not an update gamedata job\n";
    exit;
}

$job->startJob(getmypid());
$jobRepo->save($job);
$logger->info("Starting job $jobId");

$tournamentContext = $job->context;
if ($tournamentContext !== null && !($tournamentContext instanceof Tournament)) {
	$logger->warning("Job $jobId has invalid context");
    echo "Job $jobId has invalid context\n";
    exit;
}

$gameRepo = new GameRepository();
if ($tournamentContext !== null) {
    $games = $gameRepo->findAllWithoutDataByTournament($tournamentContext);
} else {
    $games = $gameRepo->findAllWithoutData();
}

$gameUpdater = new GameUpdater();

$batchSize = 50;
$delay = 10;

$count = 0;
$total = count($games);
$batches = array_chunk($games, $batchSize);

$job->addMessage("Updating $total games in ".count($batches)." Batches.");
foreach ($batches as $i=>$batch) {
    $batchNum = $i+1;
    $job->addMessage("Batch $batchNum:");

    foreach ($batch as $game) {
        $job->addMessage("Updating $game->id");

        try {
            $saveResult = $gameUpdater->updateGameData($game->id);
            switch ($saveResult['result']) {
                case SaveResult::UPDATED:
                    $job->addMessage("Updated gamedata");
                    break;
                case SaveResult::NOT_CHANGED:
                    $job->addMessage("Gamedata not changed");
                    break;
                case SaveResult::FAILED:
                    $job->addMessage("Failed to update gamedata");
                    break;
            }
        } catch (\Exception $e) {
            $job->addMessage("Error: ". $e->getMessage());
        }

        $count++;
        $job->progress = ($count / $total) * 100;
        $jobRepo->save($job);
    }
    $job->addMessage("finished batch $batchNum\n");

    if ($batchNum < count($batches)) {
        sleep($delay);
    }
}

$job->finishJob();
$jobRepo->save($job);
$logger->info("Finished job $jobId");