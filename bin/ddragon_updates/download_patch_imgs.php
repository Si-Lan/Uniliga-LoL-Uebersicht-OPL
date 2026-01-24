<?php
require_once dirname(__DIR__,2) . '/bootstrap.php';

use App\Domain\Enums\Jobs\UpdateJobAction;
use App\Domain\Enums\Jobs\UpdateJobType;
use App\Service\JobHandler;
use App\Service\Updater\PatchUpdater;

$handler = new JobHandler(UpdateJobType::ADMIN,
	UpdateJobAction::DOWNLOAD_CHAMPION_IMAGES,
	UpdateJobAction::DOWNLOAD_ITEM_IMAGES,
	UpdateJobAction::DOWNLOAD_SPELL_IMAGES,
	UpdateJobAction::DOWNLOAD_RUNE_IMAGES
);

$handler->run(function(JobHandler $handler) {
	$patchUpdater = new PatchUpdater();

	switch ($handler->job->action) {
		case UpdateJobAction::DOWNLOAD_CHAMPION_IMAGES:
			$patchUpdater->downloadChampionImgs($handler->patchContext->patchNumber, true, job: $handler->job);
			break;
		case UpdateJobAction::DOWNLOAD_ITEM_IMAGES:
			$patchUpdater->downloadItemImgs($handler->patchContext->patchNumber, true, job: $handler->job);
			break;
		case UpdateJobAction::DOWNLOAD_SPELL_IMAGES:
			$patchUpdater->downloadSummonerImgs($handler->patchContext->patchNumber, true, job: $handler->job);
			break;
		case UpdateJobAction::DOWNLOAD_RUNE_IMAGES:
			$patchUpdater->downloadRuneImgs($handler->patchContext->patchNumber, true, job: $handler->job);
			break;
		default:
			$handler->logger->warning("Job {$handler->job->id} has invalid action");
			exit;
	}
});