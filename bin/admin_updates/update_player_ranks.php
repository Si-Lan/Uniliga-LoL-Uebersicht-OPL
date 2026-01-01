<?php
require_once dirname(__DIR__,2) . '/bootstrap.php';

use App\Core\Enums\LogType;
use App\Core\Logger;
use App\Domain\Entities\Tournament;
use App\Domain\Enums\Jobs\UpdateJobAction;
use App\Domain\Enums\Jobs\UpdateJobStatus;
use App\Domain\Enums\SaveResult;
use App\Domain\Repositories\PlayerInTournamentRepository;
use App\Domain\Repositories\PlayerRepository;
use App\Domain\Repositories\UpdateJobRepository;
use App\Service\Updater\PlayerUpdater;

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
if ($job->action !== UpdateJobAction::UPDATE_PLAYER_RANKS) {
    $logger->warning("Job $jobId is not an update player ranks job");
    echo "Job $jobId is not an update player ranks job\n";
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

$job->addMessage("Updating $total players in ".count($batches)." Batches.");
foreach ($batches as $i=>$batch) {
    $batchNum = $i+1;
    $job->addMessage("Batch $batchNum:");

    foreach ($batch as $player) {
        $job->addMessage("Updating ($player->id) $player->name");

        try {
            $saveResult = $playerUpdater->updateRank($player->id);
            switch ($saveResult['player']['result']) {
                case SaveResult::UPDATED:
                    $job->addMessage("Updated rank");
                    break;
                case SaveResult::NOT_CHANGED:
                    $job->addMessage("Rank not changed");
                    break;
                case SaveResult::FAILED:
                    $job->addMessage("Failed to update rank");
                    break;
            }
            if ($saveResult['playerSeasonRank'] !== null) {
                switch ($saveResult['playerSeasonRank']['result']) {
                    case SaveResult::UPDATED:
                        $job->addMessage("Updated season rank");
                        break;
                    case SaveResult::NOT_CHANGED:
                        $job->addMessage("Season rank not changed");
                        break;
                    case SaveResult::INSERTED:
                        $job->addMessage("Created season rank");
                        break;
                    case SaveResult::FAILED:
                        $job->addMessage("Failed to update season rank");
                        break;
                }
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