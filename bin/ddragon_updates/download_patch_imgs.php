<?php
require_once dirname(__DIR__,2) . '/bootstrap.php';

use App\Core\Logger;
use App\Domain\Entities\Patch;
use App\Domain\Enums\Jobs\UpdateJobAction;
use App\Domain\Enums\Jobs\UpdateJobStatus;
use App\Domain\Repositories\UpdateJobRepository;
use App\Service\Updater\PatchUpdater;

$options = getopt('j:');
$jobId = $options['j'] ?? null;
if ($jobId === null) {
	echo "No job id given, use -j <jobid>";
	exit;
}

$jobRepo = new UpdateJobRepository();

$job = $jobRepo->findById($jobId);

if ($job === null) {
	Logger::log('ddragon_update',"Job $jobId not found");
	exit;
}
if ($job->status !== UpdateJobStatus::QUEUED) {
	Logger::log('ddragon_update',"Job $jobId is not in queued state");
	exit;
}
if ($job->action !== UpdateJobAction::DOWNLOAD_CHAMPION_IMAGES &&
	$job->action !== UpdateJobAction::DOWNLOAD_ITEM_IMAGES &&
	$job->action !== UpdateJobAction::DOWNLOAD_SPELL_IMAGES &&
	$job->action !== UpdateJobAction::DOWNLOAD_RUNE_IMAGES
) {
	Logger::log('ddragon_update',"Job $jobId is not a download patch images job");
	exit;
}

$job->startJob(getmypid());
$jobRepo->save($job);
Logger::log('ddragon_update',"Starting job $jobId");

$patchContext = $job->context;
if (!($patchContext instanceof Patch)) {
	Logger::log('ddragon_update',"Job $jobId is not a patch context");
	exit;
}

$patchUpdater = new PatchUpdater();

switch ($job->action) {
	case UpdateJobAction::DOWNLOAD_CHAMPION_IMAGES:
		$patchUpdater->downloadChampionImgs($patchContext->patchNumber, true, job: $job);
		break;
	case UpdateJobAction::DOWNLOAD_ITEM_IMAGES:
		$patchUpdater->downloadItemImgs($patchContext->patchNumber, true, job: $job);
		break;
	case UpdateJobAction::DOWNLOAD_SPELL_IMAGES:
		$patchUpdater->downloadSummonerImgs($patchContext->patchNumber, true, job: $job);
		break;
	case UpdateJobAction::DOWNLOAD_RUNE_IMAGES:
		$patchUpdater->downloadRuneImgs($patchContext->patchNumber, true, job: $job);
		break;
	default:
		Logger::log('ddragon_update',"Job $jobId has invalid action");
		exit;
}

$job->finishJob();
$jobRepo->save($job);
Logger::log('ddragon_update',"Finished job $jobId");