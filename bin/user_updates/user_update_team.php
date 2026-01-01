<?php
require_once dirname(__DIR__,2).'/bootstrap.php';

use App\Core\Enums\LogType;
use App\Core\Logger;
use App\Domain\Entities\Team;
use App\Domain\Entities\Tournament;
use App\Domain\Enums\EventType;
use App\Domain\Enums\Jobs\UpdateJobAction;
use App\Domain\Enums\Jobs\UpdateJobContextType;
use App\Domain\Enums\Jobs\UpdateJobStatus;
use App\Domain\Enums\Jobs\UpdateJobType;
use App\Domain\Repositories\MatchupRepository;
use App\Domain\Repositories\PlayerInTeamInTournamentRepository;
use App\Domain\Repositories\TeamInTournamentStageRepository;
use App\Domain\Repositories\TeamRepository;
use App\Domain\Repositories\TournamentRepository;
use App\Domain\Repositories\UpdateJobRepository;
use App\Service\Updater\GameUpdater;
use App\Service\Updater\MatchupUpdater;
use App\Service\Updater\PlayerUpdater;
use App\Service\Updater\TeamUpdater;
use App\Service\Updater\TournamentUpdater;

$logger = new Logger(LogType::USER_UPDATE);

$options = getopt('t:e:j:');
$teamId = $options['t'] ?? null;
$tournamentId = $options['e'] ?? null;
$jobId = $options['j'] ?? null;
if ($jobId !== null && ($teamId !== null || $tournamentId !== null)) {
	echo "Multiple arguments given, use either -j <jobid or -t <teamid> with -e <tournamentid>\n";
    exit;
}

$jobRepo = new UpdateJobRepository();

// Checks
if ($teamId !== null && $tournamentId !== null) {
	$teamRepo = new TeamRepository();
	$tournamentRepo = new TournamentRepository();

	$team = $teamRepo->findById($teamId);
	if ($team === null) {
		$logger->warning("Team $teamId not found");
		echo "Team $teamId not found\n";
		exit;
	}
	$tournament = $tournamentRepo->findById($tournamentId);
	if ($tournament === null) {
		$logger->warning("Event $tournamentId not found");
		echo "Event $tournamentId not found\n";
		exit;
	}
	if ($tournament->eventType !== EventType::TOURNAMENT) {
		$logger->warning("Event $tournamentId is not a tournament");
		echo "Event $tournamentId is not a tournament\n";
		exit;
	}

	$runningJob = $jobRepo->findLatest(
		UpdateJobType::USER,
		UpdateJobAction::UPDATE_TEAM,
		UpdateJobStatus::RUNNING,
		UpdateJobContextType::TEAM,
		contextId: $teamId,
		tournamentId: $tournamentId
	);

	if ($runningJob !== null) {
		$logger->warning("Team $teamId is already being updated by Job $runningJob->id");
		echo "Team $teamId is already being updated by Job $runningJob->id\n";
		exit;
	}

	$job = $jobRepo->createJob(
		UpdateJobType::USER,
		UpdateJobAction::UPDATE_TEAM,
		UpdateJobContextType::TEAM,
		contextId: $teamId,
		tournamentId: $tournamentId
	);

} else if ($jobId !== null) {
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
	if ($job->action !== UpdateJobAction::UPDATE_TEAM || $job->contextType !== UpdateJobContextType::TEAM) {
		$logger->warning("Job $jobId is not an update team job");
		echo "Job $jobId is not an update team job\n";
		exit;
	}
	$team = $job->context;
	$tournament = $job->tournament;
	if (!($team instanceof Team)) {
		$logger->warning("Job $jobId has no Team as context");
		echo "Job $jobId has no Team as context\n";
		exit;
	}
	if (!($tournament instanceof Tournament)) {
		$logger->warning("Job $jobId has no Tournament as context");
		echo "Job $jobId has no Tournament as context\n";
		exit;
	}
} else {
	echo "No arguments given, use -t <teamid> -e <tournamentid> or -j <jobid>\n";
	exit;
}

// Logic
$job->startJob(getmypid());
$jobRepo->save($job);
$logger->info("Starting job $job->id");

$teamUpdater = new TeamUpdater();
$tournamentUpdater = new TournamentUpdater();
$playerUpdater = new PlayerUpdater();

$teamSaveResult = tryAndLog(fn() => $teamUpdater->updateTeam($team->id));
$job->progress = 10;
$jobRepo->save($job);

usleep(500000);

tryAndLog(fn() => $playerUpdater->updatePlayerAccountsForTeam($team->id));

$job->progress = 20;
$jobRepo->save($job);

$playerInTeamInTournamentRepo = new PlayerInTeamInTournamentRepository();
$playersInTeamInTournament = $playerInTeamInTournamentRepo->findAllByTeamAndTournament($team, $tournament);

$playerAmount = count($playersInTeamInTournament);
foreach ($playersInTeamInTournament as $i=>$playerInTeamInTournament) {
	tryAndLog(fn() => $playerUpdater->updatePuuidByRiotId($playerInTeamInTournament->player->id));
	$job->progress = 20 + (($i+1)/$playerAmount) * 20;
	$jobRepo->save($job);
}

$teamInTournamentStageRepo = new TeamInTournamentStageRepository();
$teamInTournamentStages = $teamInTournamentStageRepo->findAllByTeamAndTournament($team,$tournament);
$matchupRepo = new MatchupRepository();
$matchupUpdater = new MatchupUpdater();
$gameUpdater = new GameUpdater();

$stagesAmount = count($teamInTournamentStages);
$progressPerLoop = 60/$stagesAmount;
$updatesInLoop = 4;
foreach ($teamInTournamentStages as $i=>$teamInTournamentStage) {
	tryAndLog(fn() => $tournamentUpdater->updateTeams($teamInTournamentStage->tournamentStage->id));
	$job->progress += $progressPerLoop / $updatesInLoop;
	$jobRepo->save($job);
	usleep(500000);

	tryAndLog(fn() => $tournamentUpdater->updateMatchups($teamInTournamentStage->tournamentStage->id));
	$job->progress += $progressPerLoop / $updatesInLoop;
	$jobRepo->save($job);
	usleep(500000);

	$matchups = $matchupRepo->findAllByTournamentStageAndTeam($teamInTournamentStage->tournamentStage, $teamInTournamentStage->teamInRootTournament);
	foreach ($matchups as $matchup) {
		$matchresultSaveResult = tryAndLog(fn() => $matchupUpdater->updateMatchupResults($matchup->id));
		if (count($matchresultSaveResult['games']) > 0) {
			foreach ($matchresultSaveResult['games'] as $game) {
				if ($game['game']->gameData === null) {
					tryAndLog(fn() => $gameUpdater->updateGameData($game['game']->id));
				}
			}
		}
		usleep(500000);
	}
	$job->progress += $progressPerLoop / $updatesInLoop;
	$jobRepo->save($job);

	$allTeamsInTournamentStage = $teamInTournamentStageRepo->findAllByTournamentStage($teamInTournamentStage->tournamentStage);
	foreach ($allTeamsInTournamentStage as $singleTeamInTournamentStage) {
		tryAndLog(fn() => $teamUpdater->updatePlayerStats($singleTeamInTournamentStage->team->id, $singleTeamInTournamentStage->tournamentStage->getRootTournament()->id));
		tryAndLog(fn() => $teamUpdater->updateStats($singleTeamInTournamentStage->team->id, $singleTeamInTournamentStage->tournamentStage->getRootTournament()->id));
	}

	tryAndLog(fn() => $tournamentUpdater->calculateStandings($teamInTournamentStage->tournamentStage->id));
	$job->progress += $progressPerLoop / $updatesInLoop;
	$jobRepo->save($job);
}


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