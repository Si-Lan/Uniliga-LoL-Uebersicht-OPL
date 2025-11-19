<?php
require_once dirname(__DIR__,2) . '/bootstrap.php';

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

$jobRepo = new UpdateJobRepository();

$runningJob = $jobRepo->findLatest(
	UpdateJobType::CRON,
	UpdateJobAction::FULL_UPDATE,
	UpdateJobStatus::RUNNING,
);

if ($runningJob !== null) {
	Logger::log('cron_update', 'A full update is already running');
	echo "A full update is already running\n";
	exit;
}

$job = $jobRepo->createJob(
	UpdateJobType::CRON,
	UpdateJobAction::FULL_UPDATE,
);

$job->startJob(getmypid());
$jobRepo->save($job);
Logger::log('cron_update', "Starting full update job $job->id");
echo "Starting full update\n";

$tournamentRepo = new TournamentRepository();
$teamInTournamentRepo = new TeamInTournamentRepository();
$playerInTournamentRepo = new PlayerInTournamentRepository();
$matchupRepo = new MatchupRepository();

$tournaments = $tournamentRepo->findAllRunningRootTournaments();
$tournamentIds = array_map(fn($tournament) => $tournament->id, $tournaments);

Logger::log('cron_update', "Found " . count($tournaments) . " running tournaments: " . implode(", ", $tournamentIds));

$tournamentUpdater = new TournamentUpdater();
$teamUpdater = new TeamUpdater();
$playerUpdater = new PlayerUpdater();
$matchupUpdater = new MatchupUpdater();

foreach ($tournaments as $i=>$tournament) {
	Logger::log('cron_update', "Starting update for tournament $tournament->id");

	// Skip finished tournaments
	$today = new DateTimeImmutable();
	if ($tournament->dateEnd < $today) {
		Logger::log('cron_update', "Tournament $tournament->id is over, skipping");
		$tournament->finished = true;
		$tournamentRepo->save($tournament);
		continue;
	}

	$stages = $tournamentRepo->findAllStandingEventsByRootTournament($tournament);
	Logger::log('cron_update', "Found " . count($stages) . " stages for tournament $tournament->id");

	// Update Teams from OPL
	foreach ($stages as $stage) {
		tryAndLog(fn() => $tournamentUpdater->updateTeams($stage->id));
		usleep(500000);
	}
	setJobProgressForTournament($i, count($tournaments), $jobRepo, $job, 10);
	Logger::log('cron_update', "updated teams for tournament $tournament->id");

	// Update Players from OPL
	$teams = $teamInTournamentRepo->findAllByRootTournament($tournament);
	Logger::log('cron_update', "Found " . count($teams) . " teams in tournament $tournament->id");
	foreach ($teams as $team) {
		tryAndLog(fn() => $teamUpdater->updatePlayers($team->team->id));
		usleep(500000);
	}
	setJobProgressForTournament($i, count($tournaments), $jobRepo, $job, 20);
	Logger::log('cron_update', "updated players for tournament $tournament->id");

	// Update Player Accounts from OPL
	$players = $playerInTournamentRepo->findAllByTournament($tournament);
	Logger::log('cron_update', "Found " . count($players) . " players in tournament $tournament->id");
	foreach ($players as $player) {
		tryAndLog(fn() => $playerUpdater->updatePlayerAccount($player->player->id));
		usleep(500000);;
	}
	setJobProgressForTournament($i, count($tournaments), $jobRepo, $job, 30);
	Logger::log('cron_update', "updated player accounts for tournament $tournament->id");

	// Update Matchups from OPL
	foreach ($stages as $stage) {
		tryAndLog(fn() => $tournamentUpdater->updateMatchups($stage->id));
		usleep(500000);
	}
	setJobProgressForTournament($i, count($tournaments), $jobRepo, $job, 40);
	Logger::log('cron_update', "updated matchups for tournament $tournament->id");

	// Update Match Results from OPL | get Game Data from Riot Games API
	$matchups = $matchupRepo->findAllByRootTournament($tournament);
	Logger::log('cron_update', "Found " . count($matchups) . " matchups in tournament $tournament->id");
	foreach ($matchups as $matchup) {
		tryAndLog(fn() => $matchupUpdater->updateMatchupResults($matchup->id));
		// da Matchresults von OPL lange Ladezeiten haben, sollte es kein Ratelimit von Riot geben
		tryAndLog(fn() => $matchupUpdater->updateGameData($matchup->id));
		usleep(500000);
	}
	setJobProgressForTournament($i, count($tournaments), $jobRepo, $job, 65);
	Logger::log('cron_update', "updated matchup results for tournament $tournament->id");

	// Update Standings by calculating from matchups
	Logger::log('cron_update', "Updating standings for tournament $tournament->id");
	foreach ($stages as $stage) {
		tryAndLog(fn() => $tournamentUpdater->calculateStandings($stage->id));
	}
	setJobProgressForTournament($i, count($tournaments), $jobRepo, $job, 70);
	Logger::log('cron_update', "finished updating standings for tournament $tournament->id");


	// RGAPI Updates haben Limit von 50 Anfragen in 10 Sekunden
	$batchSize = 50;
	$delay = 10;

	$playersWithoutPuuid = array_filter($players, fn($player) => $player->player->puuid === null);
	$playerBatches = array_chunk($players, $batchSize);
	$playerWithoutPuuidBatches = array_chunk($playersWithoutPuuid, $batchSize);

	// Update PUUIDs from Riot Games API
	Logger::log('cron_update', "Updating PUUIDs for tournament $tournament->id");
	foreach ($playerWithoutPuuidBatches as $pi=>$playerBatch) {
		$job->addMessage("Updating PUUIDs for tournament $tournament->id, batch $pi");
		foreach ($playerBatch as $player) {
			tryAndLog(fn() => $playerUpdater->updatePuuidByRiotId($player->player->id));
		}
		sleep($delay);
	}
	setJobProgressForTournament($i, count($tournaments), $jobRepo, $job, 80);
	Logger::log('cron_update', "finished updating PUUIDs for tournament $tournament->id");

	// Update Stats by calculating from matchups
	Logger::log('cron_update', "Updating stats for tournament $tournament->id");
	foreach ($teams as $team) {
		tryAndLog(fn() => $teamUpdater->updatePlayerStats($team->team->id, $tournament->id));
		tryAndLog(fn() => $teamUpdater->updateStats($team->team->id, $tournament->id));
	}
	setJobProgressForTournament($i, count($tournaments), $jobRepo, $job, 85);
	Logger::log('cron_update', "finished updating stats for tournament $tournament->id");

	// Update Player Ranks from Riot Games API
	Logger::log('cron_update', "Updating player ranks for tournament $tournament->id");
	foreach ($playerBatches as $pi=>$playerBatch) {
		$job->addMessage("Updating player ranks for tournament $tournament->id, batch $pi");
		foreach ($playerBatch as $player) {
			tryAndLog(fn() => $playerUpdater->updateRank($player->player->id));
		}
		sleep($delay);
	}
	setJobProgressForTournament($i, count($tournaments), $jobRepo, $job, 95);
	Logger::log('cron_update', "finished updating player ranks for tournament $tournament->id");

	// Update Team Ranks by calculating from Player Ranks
	Logger::log('cron_update', "Updating team ranks for tournament $tournament->id");
	foreach ($teams as $team) {
		tryAndLog(fn() => $teamUpdater->updateRank($team->team->id));
	}
	setJobProgressForTournament($i, count($tournaments), $jobRepo, $job, 100);
	Logger::log('cron_update', "finished updating team ranks for tournament $tournament->id");


	Logger::log('cron_update', "Finished tournament $tournament->id");
}

$job->finishJob();
$jobRepo->save($job);
Logger::log('cron_update', "Finished full update job $job->id");


function tryAndLog(callable $callback): mixed {
	try {
		return $callback();
	} catch (Exception $e) {
		if ($e->getCode() !== 200) Logger::log('cron_update',"Error on full cron update: \n".$e->getMessage()."\n".$e->getTraceAsString());
		return false;
	}
}

function setJobProgressForTournament(int $tournamentIndex, int $totalTournaments, UpdateJobRepository $jobRepo, UpdateJob $job, int $progress): void {
	$progressPerTournament = 100 / max($totalTournaments,1);
	$job->progress = $tournamentIndex * $progressPerTournament + $progressPerTournament * ($progress / 100);
	$jobRepo->save($job);
}