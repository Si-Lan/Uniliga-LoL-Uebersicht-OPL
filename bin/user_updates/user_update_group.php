<?php
require_once dirname(__DIR__,2).'/bootstrap.php';

use App\Core\Enums\LogType;
use App\Core\Logger;
use App\Domain\Entities\Tournament;
use App\Domain\Enums\Jobs\UpdateJobAction;
use App\Domain\Enums\Jobs\UpdateJobContextType;
use App\Domain\Enums\Jobs\UpdateJobStatus;
use App\Domain\Enums\Jobs\UpdateJobType;
use App\Domain\Repositories\MatchupRepository;
use App\Domain\Repositories\TournamentRepository;
use App\Domain\Repositories\UpdateJobRepository;
use App\Service\Updater\GameUpdater;
use App\Service\Updater\MatchupUpdater;
use App\Service\Updater\TeamUpdater;
use App\Service\Updater\TournamentUpdater;

$logger = new Logger(LogType::USER_UPDATE);

$options = getopt('g:j:');
$groupId = $options['g'] ?? null;
$jobId = $options['j'] ?? null;

if ($groupId !== null && $jobId !== null) {
	echo "Multiple arguments given, use either -g <groupid> or -j <jobid>\n";
	exit;
}

$jobRepo = new UpdateJobRepository();

// Checks
if ($groupId !== null) {
	$tournamentRepo = new TournamentRepository();
	$group = $tournamentRepo->findById($groupId);
	if ($group === null) {
		$logger->warning("Group $groupId not found");
		echo "Group $groupId not found\n";
		exit;
	}
	if (!$group->isEventWithStanding()) {
		$logger->warning("Group $groupId is not a Stage event");
		echo "Group $groupId is not a Stage event\n";
		exit;
	}

	$runningJob = $jobRepo->findLatest(
		UpdateJobType::USER,
		UpdateJobAction::UPDATE_GROUP,
		UpdateJobStatus::RUNNING,
		UpdateJobContextType::GROUP,
		$group->id
	);

	if ($runningJob !== null) {
		$logger->warning("Group $groupId is already being updated by Job $runningJob->id");
		echo "Group $groupId is already being updated by Job $runningJob->id\n";
		exit;
	}

	$job = $jobRepo->createJob(
		UpdateJobType::USER,
		UpdateJobAction::UPDATE_GROUP,
		UpdateJobContextType::GROUP,
		$group->id
	);

} elseif ($jobId !== null) {
	$job = $jobRepo->findById($jobId);
	if ($job === null) {
		$logger->warning('user_update',"Job $jobId not found");
		echo "Job $jobId not found\n";
		exit;
	}
	if ($job->status !== UpdateJobStatus::QUEUED) {
		$logger->warning("Job $jobId is not queued");
		echo "Job $jobId is not queued\n";
		exit;
	}
	if ($job->action !== UpdateJobAction::UPDATE_GROUP || $job->contextType !== UpdateJobContextType::GROUP) {
		$logger->warning("Job $jobId is not an update group job");
		echo "Job $jobId is not an update group job\n";
		exit;
	}
	$group = $job->context;
	if (!($group instanceof Tournament)) {
		$logger->warning("Job $jobId has no Event as context");
		echo "Job $jobId has no Event as context\n";
		exit;
	}

} else {
	echo "No arguments given, use -g <groupid> or -j <jobid>\n";
	exit;
}



// Logic
$job->startJob(getmypid());
$jobRepo->save($job);
$logger->info("Starting job $job->id");

$tournamentUpdater = new TournamentUpdater();

$groupSaveResult = tryAndLog(fn() => $tournamentUpdater->updateTeams($group->id));
$job->progress = 10;
$jobRepo->save($job);

usleep(500000);

$matchupSaveResult = tryAndLog(fn() => $tournamentUpdater->updateMatchups($group->id));

$job->progress = 40;
$jobRepo->save($job);

usleep(500000);

$matchupRepo = new MatchupRepository();
$matchups = $matchupRepo->findAllByTournamentStage($group, unplayedOnly: true);

$matchupUpdater = new MatchupUpdater();
$gameUpdater = new GameUpdater();
$teamUpdater = new TeamUpdater();

$matchResultSaveResults = [];
foreach ($matchups as $i=>$matchup) {
    tryAndLog(
        function() use ($matchupUpdater, $gameUpdater, $teamUpdater, $matchup, &$matchResultSaveResults) {
            $resultSaveResult = $matchupUpdater->updateMatchupResults($matchup->id);
            $matchResultSaveResults[] = $resultSaveResult;

            if (count($resultSaveResult['games']) > 0) {
                foreach ($resultSaveResult['games'] as $game) {
                    if ($game['game']->gameData === null) {
                        tryAndLog(fn() => $gameUpdater->updateGameData($game['game']->id));
                    }
                }

                tryAndLog(fn() => $teamUpdater->updatePlayerStats($matchup->team1->team->id, $matchup->tournamentStage->getRootTournament()->id));
                tryAndLog(fn() => $teamUpdater->updateStats($matchup->team1->team->id, $matchup->tournamentStage->getRootTournament()->id));
                tryAndLog(fn() => $teamUpdater->updatePlayerStats($matchup->team2->team->id, $matchup->tournamentStage->getRootTournament()->id));
                tryAndLog(fn() => $teamUpdater->updateStats($matchup->team2->team->id, $matchup->tournamentStage->getRootTournament()->id));
            }
        }
    );
    $job->progress = 40 + (($i+1) / count($matchups)) * 50;
    $jobRepo->save($job);
    usleep(500000);
}
tryAndLog(fn() => $tournamentUpdater->calculateStandings($group->id));

$job->finishJob();
$jobRepo->save($job);
$logger->info("Finished job $job->id");


function tryAndLog(callable $callback): mixed {
    global $logger, $group;
    try {
        return $callback();
    } catch (Exception $e) {
        $logger->error("Error updating group $group->id: \n".$e->getMessage()."\n".$e->getTraceAsString());
        return false;
    }
}