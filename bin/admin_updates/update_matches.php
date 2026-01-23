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
	UpdateJobAction::UPDATE_MATCHES
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
		$handler->addMessageAndResult(
			"Aktualisiere Matchups für ($tournament->id) {$tournament->getShortName()}"
		);

		try {
			$saveResult = $tournamentUpdater->updateMatchups($tournament->id);

			foreach ($saveResult["matchups"] as $matchupResult) {
				$resultHandler->handleSaveResult($matchupResult->result, "Matchup {$matchupResult->entity?->id}");
			}
			$resultHandler->handleSaveResults($saveResult['matchups'], "Matchups");

			foreach ($saveResult["removedMatchups"] as $matchup) {
				$handler->addMessage("Matchup $matchup->id entfernt");
			}
			if (count($saveResult["removedMatchups"]) !== 0) $handler->addResultMessage(count($saveResult["removedMatchups"])." Matchups entfernt");
		} catch (\Exception $e) {
			$errorMessage = "Error: ". $e->getMessage()." in ".$e->getFile()." Line ".$e->getLine();
			$handler->logger->error($errorMessage."\n".$e->getTraceAsString());
			$handler->addMessageAndResult($errorMessage);
		}

		$handler->setProgress(($i + 1) / count($tournaments) * 100);
	}
});