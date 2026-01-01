<?php
require_once dirname(__DIR__,2) . '/bootstrap.php';

use App\Core\Enums\LogType;
use App\Core\Logger;
use App\Domain\Entities\Tournament;
use App\Domain\Enums\Jobs\UpdateJobAction;
use App\Domain\Enums\Jobs\UpdateJobStatus;
use App\Domain\Enums\SaveResult;
use App\Domain\Repositories\TeamInTournamentRepository;
use App\Domain\Repositories\TeamRepository;
use App\Domain\Repositories\UpdateJobRepository;
use App\Service\Updater\TeamUpdater;

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
if ($job->action !== UpdateJobAction::UPDATE_TEAM_RANKS) {
    $logger->warning("Job $jobId is not an update team ranks job");
    echo "Job $jobId is not an update team ranks job\n";
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
    $teamInTournamentRepo = new TeamInTournamentRepository();
    $teams = $teamInTournamentRepo->findAllByRootTournament($tournamentContext);
    $teams = array_map(fn($team) => $team->team, $teams);
} else {
    $teamRepo = new TeamRepository();
    $teams = $teamRepo->findAll();
}

$teamUpdater = new TeamUpdater();

$count = 0;
$total = count($teams);
$job->addMessage("Updating $total teams");
foreach ($teams as $team) {
    $job->addMessage("Updating ($team->id) $team->name");

    try {
        $saveResult = $teamUpdater->updateRank($team->id);
        switch ($saveResult['team']['result']) {
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
        if ($saveResult['tournamentSeasonRanks'] !== null) {
            foreach ($saveResult['tournamentSeasonRanks'] as $tournamentSeasonRank) {
                switch ($tournamentSeasonRank['result']) {
                    case SaveResult::UPDATED:
                        $job->addMessage("Updated season rank in a tournament");
                        break;
                    case SaveResult::NOT_CHANGED:
                        $job->addMessage("Season rank in a tournament not changed");
                        break;
                    case SaveResult::INSERTED:
                        $job->addMessage("Created season rank in a tournament");
                        break;
                    case SaveResult::FAILED:
                        $job->addMessage("Failed to update season rank in a tournament");
                        break;
                }
            }
        }
    } catch (\Exception $e) {
        $job->addMessage("Error: ". $e->getMessage());
    }

    $count++;
    $job->progress = ($count / $total) * 100;
    $jobRepo->save($job);
}

$job->finishJob();
$jobRepo->save($job);
$logger->info("Finished job $jobId");