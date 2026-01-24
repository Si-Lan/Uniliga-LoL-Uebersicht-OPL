<?php
require_once dirname(__DIR__,2) . '/bootstrap.php';

use App\Core\Enums\LogType;
use App\Core\Logger;
use App\Domain\Entities\UpdateJob;
use App\Domain\Enums\Jobs\UpdateJobAction;
use App\Domain\Enums\Jobs\UpdateJobStatus;
use App\Domain\Enums\Jobs\UpdateJobType;
use App\Domain\Repositories\MatchupRepository;
use App\Domain\Repositories\PlayerInTournamentRepository;
use App\Domain\Repositories\TeamInTournamentRepository;
use App\Domain\Repositories\TournamentRepository;
use App\Domain\Repositories\UpdateJobRepository;
use App\Service\Updater\MatchupUpdater;
use App\Service\Updater\PlayerUpdater;
use App\Service\Updater\TeamUpdater;
use App\Service\Updater\TournamentUpdater;

$logger = new Logger(LogType::CRON_UPDATE);

$jobRepo = new UpdateJobRepository();

$runningJob = $jobRepo->findLatest(
	UpdateJobType::CRON,
	UpdateJobAction::FULL_UPDATE,
	UpdateJobStatus::RUNNING,
);

if ($runningJob !== null) {
	$logger->warning('A full update is already running');
	echo "A full update is already running\n";
	exit;
}

$job = $jobRepo->createJob(
	UpdateJobType::CRON,
	UpdateJobAction::FULL_UPDATE,
);

$job->startJob(getmypid());
$jobRepo->save($job);
$logger->info("Starting full update job $job->id");
echo "Starting full update\n";

$tournamentRepo = new TournamentRepository();
$teamInTournamentRepo = new TeamInTournamentRepository();
$playerInTournamentRepo = new PlayerInTournamentRepository();
$matchupRepo = new MatchupRepository();

$tournaments = $tournamentRepo->findAllRunningRootTournaments();
$tournamentIds = array_map(fn($tournament) => $tournament->id, $tournaments);

$logger->info("Found " . count($tournaments) . " running tournaments: " . implode(", ", $tournamentIds));

$tournamentUpdater = new TournamentUpdater();
$teamUpdater = new TeamUpdater();
$playerUpdater = new PlayerUpdater();
$matchupUpdater = new MatchupUpdater();

foreach ($tournaments as $i=>$tournament) {
	$logger->info("Starting update for tournament $tournament->id");

	// Skip finished tournaments
	$today = new DateTimeImmutable();
	if ($tournament->dateEnd < $today) {
		$logger->info("Tournament $tournament->id is over, skipping");
		$tournament->finished = true;
		$tournamentRepo->save($tournament);
		continue;
	}

	$stages = $tournamentRepo->findAllStandingEventsByRootTournament($tournament);
	$logger->info("Found " . count($stages) . " stages for tournament $tournament->id");

	// Update Teams from OPL
	foreach ($stages as $stage) {
		tryAndLog(fn() => $tournamentUpdater->updateTeams($stage->id));
		usleep(500000);
	}
	setJobProgressForTournament($i, count($tournaments), $jobRepo, $job, 10);
	$logger->info("updated teams for tournament $tournament->id");

	// Update Players from OPL
	$teams = $teamInTournamentRepo->findAllByRootTournament($tournament);
	$logger->info("Found " . count($teams) . " teams in tournament $tournament->id");
	foreach ($teams as $team) {
		tryAndLog(fn() => $teamUpdater->updatePlayers($team->team->id));
		usleep(500000);
	}
	setJobProgressForTournament($i, count($tournaments), $jobRepo, $job, 20);
	$logger->info("updated players for tournament $tournament->id");

	// Update Player Accounts from OPL
	$players = $playerInTournamentRepo->findAllByTournament($tournament);
	$logger->info("Found " . count($players) . " players in tournament $tournament->id");
	foreach ($players as $player) {
		tryAndLog(fn() => $playerUpdater->updatePlayerAccount($player->player->id));
		usleep(500000);;
	}
	setJobProgressForTournament($i, count($tournaments), $jobRepo, $job, 30);
	$logger->info("updated player accounts for tournament $tournament->id");

	// Update Matchups from OPL
	foreach ($stages as $stage) {
		tryAndLog(fn() => $tournamentUpdater->updateMatchups($stage->id));
		usleep(500000);
	}
	setJobProgressForTournament($i, count($tournaments), $jobRepo, $job, 40);
	$logger->info("updated matchups for tournament $tournament->id");

	// Update Match Results from OPL | get Game Data from Riot Games API
	$matchups = $matchupRepo->findAllByRootTournament($tournament);
	$logger->info("Found " . count($matchups) . " matchups in tournament $tournament->id");
	foreach ($matchups as $matchup) {
		tryAndLog(fn() => $matchupUpdater->updateMatchupResults($matchup->id));
		// da Matchresults von OPL lange Ladezeiten haben, sollte es kein Ratelimit von Riot geben
		tryAndLog(fn() => $matchupUpdater->updateGameData($matchup->id));
		usleep(500000);
	}
	setJobProgressForTournament($i, count($tournaments), $jobRepo, $job, 65);
	$logger->info("updated matchup results for tournament $tournament->id");

	// Update Standings by calculating from matchups
	$logger->info("Updating standings for tournament $tournament->id");
	foreach ($stages as $stage) {
		tryAndLog(fn() => $tournamentUpdater->calculateStandings($stage->id));
	}
	setJobProgressForTournament($i, count($tournaments), $jobRepo, $job, 70);
	$logger->info("finished updating standings for tournament $tournament->id");


	// RGAPI Updates haben Limit von 50 Anfragen in 10 Sekunden
	$batchSize = 50;
	$delay = 10;

	$playersWithoutPuuid = array_filter($players, fn($player) => $player->player->puuid === null);
	$playerBatches = array_chunk($players, $batchSize);
	$playerWithoutPuuidBatches = array_chunk($playersWithoutPuuid, $batchSize);

	// Update PUUIDs from Riot Games API
	$logger->info("Updating PUUIDs for tournament $tournament->id");
	foreach ($playerWithoutPuuidBatches as $pi=>$playerBatch) {
		$job->addMessage("Updating PUUIDs for tournament $tournament->id, batch $pi");
		foreach ($playerBatch as $player) {
			tryAndLog(fn() => $playerUpdater->updatePuuidByRiotId($player->player->id));
		}
		sleep($delay);
	}
	setJobProgressForTournament($i, count($tournaments), $jobRepo, $job, 80);
	$logger->info("finished updating PUUIDs for tournament $tournament->id");

	// Update Stats by calculating from matchups
	$logger->info("Updating stats for tournament $tournament->id");
	foreach ($teams as $team) {
		tryAndLog(fn() => $teamUpdater->updatePlayerStats($team->team->id, $tournament->id));
		tryAndLog(fn() => $teamUpdater->updateStats($team->team->id, $tournament->id));
	}
	setJobProgressForTournament($i, count($tournaments), $jobRepo, $job, 85);
	$logger->info("finished updating stats for tournament $tournament->id");

	// Update Player Ranks from Riot Games API
	$logger->info("Updating player ranks for tournament $tournament->id");
	foreach ($playerBatches as $pi=>$playerBatch) {
		$job->addMessage("Updating player ranks for tournament $tournament->id, batch $pi");
		foreach ($playerBatch as $player) {
			tryAndLog(fn() => $playerUpdater->updateRank($player->player->id));
		}
		sleep($delay);
	}
	setJobProgressForTournament($i, count($tournaments), $jobRepo, $job, 95);
	$logger->info("finished updating player ranks for tournament $tournament->id");

	// Update Team Ranks by calculating from Player Ranks
	$logger->info("Updating team ranks for tournament $tournament->id");
	foreach ($teams as $team) {
		tryAndLog(fn() => $teamUpdater->updateRank($team->team->id));
	}
	setJobProgressForTournament($i, count($tournaments), $jobRepo, $job, 100);
	$logger->info("finished updating team ranks for tournament $tournament->id");


	$tournamentCurrent = $tournamentRepo->findById($tournament->id, ignoreCache: true);
	$tournamentCurrent->lastCronUpdate = new DateTimeImmutable();
	$tournamentRepo->save($tournamentCurrent);

	$logger->info("Finished tournament $tournament->id");
}

$job->finishJob();
$jobRepo->save($job);
$logger->info("Finished full update job $job->id");


function tryAndLog(callable $callback): mixed {
	global $logger;
	try {
		return $callback();
	} catch (Throwable $e) {
		if ($e->getCode() !== 200) $logger->error("Error on full cron update: \n".$e->getMessage()."\n".$e->getTraceAsString());
		return false;
	}
}

function setJobProgressForTournament(int $tournamentIndex, int $totalTournaments, UpdateJobRepository $jobRepo, UpdateJob $job, int $progress): void {
	$progressPerTournament = 100 / max($totalTournaments,1);
	$job->progress = $tournamentIndex * $progressPerTournament + $progressPerTournament * ($progress / 100);
	$jobRepo->save($job);
}