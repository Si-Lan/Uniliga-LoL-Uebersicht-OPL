<?php
require_once dirname(__DIR__,2) . '/bootstrap.php';

use App\Core\Logger;
use App\Domain\Entities\Tournament;
use App\Domain\Entities\UpdateJob;
use App\Domain\Enums\Jobs\UpdateJobAction;
use App\Domain\Enums\Jobs\UpdateJobStatus;
use App\Domain\Enums\SaveResult;
use App\Domain\Repositories\PlayerInTournamentRepository;
use App\Domain\Repositories\PlayerRepository;
use App\Domain\Repositories\UpdateJobRepository;
use App\Service\Updater\PlayerUpdater;

$options = getopt('j:');
$jobId = $options['j'] ?? null;
if ($jobId === null) {
    echo "No jobId given, use -j <jobid>\n";
    exit;
}

$jobRepo = new UpdateJobRepository();
$job = $jobRepo->findById($jobId);

if ($job === null) {
    Logger::log('admin_update', "Job $jobId not found");
    echo "Job $jobId not found\n";
    exit;
}
if ($job->status !== UpdateJobStatus::QUEUED) {
    Logger::log('admin_update', "Job $jobId is not queued");
    echo "Job $jobId is not queued\n";
    exit;
}
if ($job->action !== UpdateJobAction::UPDATE_RIOTIDS_PUUIDS) {
    Logger::log('admin_update', "Job $jobId is not an updated riotIds by Puuids job");
    echo "Job $jobId is not an updated riotIds by Puuids job\n";
    exit;
}

$job->startJob(getmypid());
$jobRepo->save($job);
Logger::log('admin_update', "Job $jobId started.");

$tournamentContext = $job->context;
if ($tournamentContext !== null && !($tournamentContext instanceof Tournament)) {
    Logger::log('admin_update', "Job $jobId is not a tournament context");
    echo "Job $jobId has invalid context\n";
    exit;
}

if ($tournamentContext !== null) {
    $playerInTournamentRepo = new PlayerInTournamentRepository();
    $players = $playerInTournamentRepo->findAllByTournament($tournamentContext);
    $players = array_map(fn($player) => $player->player, $players);
} else {
    $playerRepo = new PlayerRepository();
    $players = $playerRepo->findAll();
}

$playerUpdater = new PlayerUpdater();

$batchSize = 50;
$delay = 10;

$count = 0;
$total = count($players);
$batches = array_chunk($players, $batchSize);

$job->addMessage("Updating $total players in ".count($players)." Batches.");
foreach ($batches as $i=>$batch) {
    $batchNum = $i+1;
    $job->addMessage("Batch $batchNum:");

    foreach ($batch as $player) {
        $job->addMessage("Updating ($player->id) $player->name");

        try {
            $saveResult = $playerUpdater->updateRiotIdByPuuid($player->id);
            switch ($saveResult['result']) {
                case SaveResult::UPDATED:
                    $job->addMessage("Updated RiotId");
                    break;
                case SaveResult::NOT_CHANGED:
                    $job->addMessage("RiotId not changed");
                    break;
                case SaveResult::FAILED:
                    $job->addMessage("Update failed");
                    break;
            }
        } catch (\Exception $e) {
            $job->addMessage("Error: ". $e->getMessage());
        }

        $count++;
        $job->progress = ($count / $total) * 100;
        $jobRepo->save($job);
    }
    $job->addMessage("finished Batch $batchNum\n");

    if ($batchNum < count($batches)) {
        sleep($delay);
    }
}

$job->finishJob($jobId);
$jobRepo->save($job);
Logger::log('admin_update', "Finished job $jobId");