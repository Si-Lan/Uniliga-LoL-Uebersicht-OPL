<?php
require_once dirname(__DIR__,2) . '/bootstrap.php';

use App\Domain\Enums\EventType;
use App\Domain\Enums\Jobs\UpdateJobAction;
use App\Domain\Enums\Jobs\UpdateJobType;
use App\Domain\Repositories\TeamInTournamentRepository;
use App\Service\JobHandler;
use App\Service\ResultHandler;
use App\Service\Updater\TeamUpdater;

$handler = new JobHandler(
	UpdateJobType::ADMIN,
	UpdateJobAction::UPDATE_PLAYERS
);
$resultHandler = new ResultHandler($handler);

$handler->run(function(JobHandler $handler) use ($resultHandler) {
	$teamInTournamentRepo = new TeamInTournamentRepository();

	if ($handler->tournamentContext->eventType === EventType::TOURNAMENT) {
		$teams = $teamInTournamentRepo->findAllByRootTournament($handler->tournamentContext);
	} elseif ($handler->tournamentContext->isStage()) {
		$teams = $teamInTournamentRepo->findAllByStage($handler->tournamentContext);
	} else {
		$teams = $teamInTournamentRepo->findAllByParentTournament($handler->tournamentContext);
	}

	if (count($teams) === 0) return;

	$teamUpdater = new TeamUpdater();

	foreach ($teams as $i=>$team) {
		if ($i !== 0) {
			usleep(500000);
			$handler->addMessageAndResult("");
		}
		$handler->addMessageAndResult("Aktualisiere Spieler für ({$team->team->id}) {$team->team->name}");

		try {
			$saveResult = $teamUpdater->updatePlayers($team->team->id);

			foreach ($saveResult["players"] as $playerResult) {
				$resultHandler->handleSaveResult($playerResult->result, "Spieler {$playerResult->entity?->id}");
			}
			$resultHandler->handleSaveResults($saveResult['players'], "Spieler");

			foreach ($saveResult["addedPlayers"] as $addedPlayer) {
				$handler->addMessage("Spieler $addedPlayer->id zu Team hinzugefügt");
			}
			if (count($saveResult["addedPlayers"]) !== 0) $handler->addResultMessage(
				count($saveResult["addedPlayers"])." Spieler zu Team hinzugefügt"
			);

			foreach ($saveResult["removedPlayers"] as $removedPlayer) {
				$handler->addMessage("Spieler $removedPlayer->id aus Team entfernt");
			}
			if (count($saveResult["removedPlayers"]) !== 0) $handler->addResultMessage(
				count($saveResult["removedPlayers"])." Spieler aus Team entfernt"
			);

			foreach ($saveResult["tournamentChanges"] as $tournamentChange) {
				if (count($tournamentChange["addedPlayers"]) === 0 && count($tournamentChange["removedPlayers"]) === 0) {
					continue;
				}
				$handler->addMessageAndResult("In Turnier ({$tournamentChange["tournament"]?->id}) ".$tournamentChange["tournament"]?->getFullName());

				foreach ($tournamentChange["addedPlayers"] as $addedPlayer) {
					$handler->addMessage("Spieler $addedPlayer->id zu Team im Turnier hinzugefügt");
				}
				if (count($tournamentChange["addedPlayers"]) !== 0) $handler->addResultMessage(
					count($tournamentChange["addedPlayers"])." Spieler zum Team im Turnier hinzugefügt"
				);

				foreach ($tournamentChange["removedPlayers"] as $removedPlayer) {
					$handler->addMessage("Spieler $removedPlayer->id aus Team im Turnier entfernt");
				}
				if (count($tournamentChange["removedPlayers"]) !== 0) $handler->addResultMessage(
					count($tournamentChange["removedPlayers"])." Spieler aus Team im Turnier entfernt"
				);
			}

		} catch (\Exception $e) {
			$errorMessage = "Error: ". $e->getMessage()." in ".$e->getFile()." Line ".$e->getLine();
			$handler->logger->error($errorMessage."\n".$e->getTraceAsString());
			$handler->addMessageAndResult($errorMessage);
		}

		$handler->setProgress(($i + 1) / count($teams) * 100);
	}
});