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

$options = getopt('j:', ['without-puuid']);
$jobId = $options['j'] ?? null;
if ($jobId === null) {
	echo "No job id given, use -j <jobid>";
	exit;
}
$withoutid = isset($options['without-puuid']);

$jobRepo = new UpdateJobRepository();

$job = $jobRepo->findById($jobId);

if ($job === null) {
	$logger->warning("Job $jobId not found");
	exit;
}
if ($job->status !== UpdateJobStatus::QUEUED) {
	$logger->warning("Job $jobId is not in queued state");
	exit;
}
if ($job->action !== UpdateJobAction::UPDATE_PUUIDS) {
	$logger->warning("Job $jobId is not an update puuids job");
	exit;
}

$job->startJob(getmypid());
$jobRepo->save($job);
$logger->info("Starting job $jobId");

$tournamentContext = $job->context;
if ($tournamentContext !== null && !($tournamentContext instanceof Tournament)) {
	$logger->info("Job $jobId has invalid context");
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

if ($withoutid) {
	$players = array_filter($players, fn($player) => $player->puuid === null);
}

$playerUpdater = new PlayerUpdater();

$batchSize = 50;
$delay = 10;

$count = 0;
$total = count($players);
$batches = array_chunk($players, $batchSize);
$job->addMessage("Updating $total players in ".count($batches)." Batches");
foreach ($batches as $i=>$batch) {
	$batchNum = $i+1;
	$job->addMessage("Batch $batchNum:");
	foreach ($batch as $player) {
		$job->addMessage("Updating ($player->id) $player->name");

		try {
			$saveResult = $playerUpdater->updatePuuidByRiotId($player->id);
			switch ($saveResult['result']) {
				case SaveResult::UPDATED:
					$job->addMessage("Updated puuid");
					break;
				case SaveResult::NOT_CHANGED:
					$job->addMessage("puuid not changed");
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