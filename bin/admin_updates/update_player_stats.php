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
if ($job->action !== UpdateJobAction::UPDATE_PLAYER_STATS) {
    $logger->warning("Job $jobId is not an update player stats job");
    echo "Job $jobId is not an update player stats job\n";
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

$count = 0;
$total = count($players);
$job->addMessage("Updating $total players");
foreach ($players as $player) {
    $job->addMessage("Updating ($player->id) $player->name");

    try {
        $saveResult = $playerUpdater->updateStats($player->id, $tournamentContext->id);
        switch ($saveResult['playerInTournament']['result']) {
            case SaveResult::UPDATED:
                $job->addMessage("Updated stats");
                break;
            case SaveResult::NOT_CHANGED:
                $job->addMessage("Stats not changed");
                break;
            case SaveResult::INSERTED:
                $job->addMessage("Inserted new stats");
                break;
            case SaveResult::FAILED:
                $job->addMessage("Failed to update stats");
                break;
        }
        if (count($saveResult['playerInTeamsInTournament']) > 0) {
            foreach ($saveResult['playerInTeamsInTournament'] as $playerInTeamInTournament) {
                switch ($playerInTeamInTournament['result']) {
                    case SaveResult::UPDATED:
                        $job->addMessage("Updated stats in a team");
                        break;
                    case SaveResult::NOT_CHANGED:
                        $job->addMessage("Stats in a team not changed");
                        break;
                    case SaveResult::INSERTED:
                        $job->addMessage("Inserted new stats in a team");
                        break;
                    case SaveResult::FAILED:
                        $job->addMessage("Failed to update stats in a team");
                        break;
                }
            }
        }
    } catch (\Exception $e) {
        $job->addMessage("Error: ". $e->getMessage());
        $logger->error("Error: ". $e->getMessage(). "\n".$e->getTraceAsString());
    }

    $count++;
    $job->progress = ($count / $total) * 100;
    $jobRepo->save($job);
}

$job->finishJob();
$jobRepo->save($job);
$logger->info("Finished job $jobId");