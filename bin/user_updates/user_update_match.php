<?php
require_once dirname(__DIR__,2).'/bootstrap.php';

use App\Core\Enums\LogType;
use App\Core\Logger;
use App\Domain\Entities\Matchup;
use App\Domain\Enums\Jobs\UpdateJobAction;
use App\Domain\Enums\Jobs\UpdateJobContextType;
use App\Domain\Enums\Jobs\UpdateJobStatus;
use App\Domain\Enums\Jobs\UpdateJobType;
use App\Domain\Repositories\MatchupRepository;
use App\Domain\Repositories\UpdateJobRepository;
use App\Service\Updater\GameUpdater;
use App\Service\Updater\MatchupUpdater;
use App\Service\Updater\TeamUpdater;
use App\Service\Updater\TournamentUpdater;

$logger = new Logger(LogType::USER_UPDATE);

$options = getopt('m:j:');
$matchId = $options['m'] ?? null;
$jobId = $options['j'] ?? null;

if ($matchId !== null && $jobId !== null) {
	echo "Multiple arguments given, use either -m <matchid> or -j <jobid>\n";
	exit;
}

$jobRepo = new UpdateJobRepository();

// Checks
if ($matchId !== null) {
	$matchRepo = new MatchupRepository();
	$match = $matchRepo->findById($matchId);
	if ($match === null) {
		$logger->warning("Match $matchId not found");
		echo "Match $matchId not found\n";
		exit;
	}
	$runningJob = $jobRepo->findLatest(
		UpdateJobType::USER,
		UpdateJobAction::UPDATE_MATCH,
		UpdateJobStatus::RUNNING,
		UpdateJobContextType::MATCHUP,
		$match->id
	);
	if ($runningJob !== null) {
		$logger->warning("Match $matchId is already being updated by Job $runningJob->id");
		echo "Match $matchId is already being updated by Job $runningJob->id\n";
		exit;
	}

	$job = $jobRepo->createJob(
		UpdateJobType::USER,
		UpdateJobAction::UPDATE_MATCH,
		UpdateJobContextType::MATCHUP,
		$match->id
	);

} elseif ($jobId !== null) {
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
	if ($job->action !== UpdateJobAction::UPDATE_MATCH || $job->contextType !== UpdateJobContextType::MATCHUP) {
		$logger->warning("Job $jobId is not an update match job");
		echo "Job $jobId is not an update match job\n";
		exit;
	}
	$match = $job->context;
	if (!($match instanceof Matchup)) {
		$logger->warning("Job $jobId has no Matchup as context");
		echo "Job $jobId has no Matchup as context\n";
		exit;
	}

} else {
	echo "No arguments given, use -m <matchid> or -j <jobid>\n";
	exit;
}



// Logic
$job->startJob(getmypid());
$job->progress = 10;
$jobRepo->save($job);
$logger->info("Starting job $job->id");

$matchUpdater = new MatchupUpdater();

$matchResultSaveResult = tryAndLog(fn() => $matchUpdater->updateMatchupResults($match->id));
$job->progress = 40;
$jobRepo->save($job);

$gameUpdater = new GameUpdater();
$gameAmount = count($matchResultSaveResult['games']);
if ($gameAmount > 0) {
	foreach ($matchResultSaveResult['games'] as $i=>$game) {
		if ($game->entity->gameData === null) {
			tryAndLog(fn() => $gameUpdater->updateGameData($game->entity->id));
		}
		$job->progress = 40 + (($i+1)/$gameAmount) * 40;
	}
}

$savedMatchup = $matchResultSaveResult['matchup']->entity;
$teams = [$savedMatchup->team1?->team, $savedMatchup->team2?->team];
$teamUpdater = new TeamUpdater();
foreach ($teams as $team) {
    if ($team === null) continue;
	tryAndLog(fn() => $teamUpdater->updatePlayerStats($team->id, $savedMatchup->tournamentStage->getRootTournament()->id));
	tryAndLog(fn() => $teamUpdater->updateStats($team->id, $savedMatchup->tournamentStage->getRootTournament()->id));
}
$job->progress = 90;
$jobRepo->save($job);

$tournamentUpdater = new TournamentUpdater();
tryAndLog(fn() => $tournamentUpdater->calculateStandings($savedMatchup->tournamentStage->id));

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