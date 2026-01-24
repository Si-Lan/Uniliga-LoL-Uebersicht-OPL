<?php
require_once dirname(__DIR__,2) . '/bootstrap.php';

use App\Domain\Enums\EventType;
use App\Domain\Enums\Jobs\UpdateJobAction;
use App\Domain\Enums\Jobs\UpdateJobType;
use App\Domain\Repositories\TournamentRepository;
use App\Service\JobHandler;
use App\Service\ResultHandler;
use App\Service\Updater\TournamentUpdater;

$handler = new JobHandler(
	UpdateJobType::ADMIN,
	UpdateJobAction::UPDATE_STANDINGS
);
$resultHandler = new ResultHandler($handler);

$handler->run(function (JobHandler $handler) use ($resultHandler) {
	$tournamentRepo = new TournamentRepository();
	if ($handler->tournamentContext->isEventWithStanding()) {
		$tournaments = [$handler->tournamentContext];
	} elseif ($handler->tournamentContext->eventType === EventType::TOURNAMENT) {
		$tournaments = $tournamentRepo->findAllStandingEventsByRootTournament($handler->tournamentContext);
	} else {
		$tournaments = $tournamentRepo->findAllStandingEventsByParentTournament($handler->tournamentContext);
	}

	if (count($tournaments) === 0) return;

	$tournamentUpdater = new TournamentUpdater();

	foreach ($tournaments as $i=>$tournament) {
		if ($i !== 0) $handler->addMessageAndResult("");
		$handler->addMessageAndResult("Aktualisiere Tabelle für ($tournament->id) {$tournament->getShortName()}");

		try {
			$saveResult = $tournamentUpdater->calculateStandings($tournament->id);
			foreach ($saveResult as $result) {
				$resultHandler->handleSaveResult($result->result, "Standings für Team {$result->entity?->team?->id}");
			}
			$resultHandler->handleSaveResults($saveResult, "Standings für Teams");
		} catch (\Exception $e) {
			$errorMessage = "Error: ".$e->getMessage()." in ".$e->getFile()." Line ".$e->getLine();
			$handler->logger->error($errorMessage."\n".$e->getTraceAsString());
			$handler->addMessageAndResult($errorMessage);
		}

		$handler->setProgress(($i + 1) / count($tournaments) * 100);
	}
});