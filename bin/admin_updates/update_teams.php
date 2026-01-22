<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\Domain\Enums\EventType;
use App\Domain\Enums\Jobs\UpdateJobAction;
use App\Domain\Enums\Jobs\UpdateJobType;
use App\Domain\Repositories\TournamentRepository;
use App\Service\JobHandler;
use App\Service\ResultHandler;
use App\Service\Updater\TournamentUpdater;

$handler = new JobHandler(UpdateJobType::ADMIN, UpdateJobAction::UPDATE_TEAMS);
$resultHandler = new ResultHandler($handler);

$handler->run(function(JobHandler $handler) use($resultHandler) {
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
		$handler->addMessageAndResult("Aktualisiere Teams für ($tournament->id) {$tournament->getShortName()}");

		try {
			$saveResult = $tournamentUpdater->updateTeams($tournament->id);

			foreach ($saveResult["teams"] as $teamResult) {
				$resultHandler->handleSaveResult($teamResult->result, "Team {$teamResult->entity?->id}");
			}
			$resultHandler->handleSaveResults($saveResult['teams'], "Teams");

			foreach ($saveResult["addedTeams"] as $team) {
				$handler->addMessage("Team $team->id zu Turnier hinzugefügt");
			}
			if (count($saveResult["addedTeams"]) !== 0) $handler->addResultMessage(count($saveResult["addedTeams"])." Teams zu Turnier hinzugefügt");

			foreach ($saveResult["removedTeams"] as $team) {
				$handler->addMessage("Team $team->id von Turnier entfernt");
			}
			if (count($saveResult["removedTeams"]) !== 0) $handler->addResultMessage(count($saveResult["removedTeams"])." Teams von Turnier entfernt");
		} catch (\Exception $e) {
			$errorMessage = "Error: ".$e->getMessage()." in ".$e->getFile()." Line ".$e->getLine();
			$handler->addMessageAndResult($errorMessage);
			$handler->logger->error($errorMessage."\n".$e->getTraceAsString());
		}

		$handler->setProgress((($i + 1) / count($tournaments)) * 100);
	}
});