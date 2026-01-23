<?php
require_once dirname(__DIR__,2) . '/bootstrap.php';

use App\Domain\Enums\EventType;
use App\Domain\Enums\Jobs\UpdateJobAction;
use App\Domain\Enums\Jobs\UpdateJobType;
use App\Domain\Repositories\PlayerInTeamRepository;
use App\Domain\Repositories\TeamInTournamentRepository;
use App\Service\JobHandler;
use App\Service\ResultHandler;
use App\Service\Updater\PlayerUpdater;

$handler = new JobHandler(
	UpdateJobType::ADMIN,
	UpdateJobAction::UPDATE_RIOTIDS_OPL
);
$resultHandler = new ResultHandler($handler);

$handler->run(function (JobHandler $handler) use ($resultHandler) {
	$teamInTournamentRepo = new TeamInTournamentRepository();

	if ($handler->tournamentContext->eventType === EventType::TOURNAMENT) {
		$teams = $teamInTournamentRepo->findAllByRootTournament($handler->tournamentContext);
	} elseif ($handler->tournamentContext->isStage()) {
		$teams = $teamInTournamentRepo->findAllByStage($handler->tournamentContext);
	} else {
		$teams = $teamInTournamentRepo->findAllByParentTournament($handler->tournamentContext);
	}

	if (count($teams) === 0) return;

	$playerInTeamRepo = new PlayerInTeamRepository();
	$playerUpdater = new PlayerUpdater();

	$progressPerTeam = 100/(count($teams));
	foreach ($teams as $i=>$team) {
		$progressAfterTeam = ($i + 1) * $progressPerTeam;
		if ($i !== 0) {
			$handler->addMessageAndResult("");
		}
		$handler->addMessageAndResult(
			"Aktualisiere Spieler-Accounts für ({$team->team->id}) {$team->team->name}"
		);

		$players = $playerInTeamRepo->findAllByTeamAndActiveStatus($team->team, active: true);
		if (count($players) === 0) {
			$handler->addMessageAndResult(
				"keine Spieler im Team"
			);
			$handler->setProgress($progressAfterTeam);
			continue;
		}

		$saveResults = [];
		$errors = 0;
		$progressPerPlayer = $progressPerTeam/(count($players));
		foreach ($players as $j=>$player) {
			$progressAfterPlayer = $i * $progressPerTeam + (($j + 1) * $progressPerPlayer);
			if ($j !== 0) {
				usleep(500000);
			}
			$handler->addMessage(
				"Spieler ({$player->player->id}): {$player->player->name}"
			);

			try {
				$saveResult = $playerUpdater->updatePlayerAccount($player->player->id);
				$saveResults[] = $saveResult;
				$resultHandler->handleSaveResult($saveResult->result, "RiotId");
			} catch (\Exception $e) {
				if ($e->getCode() !== 200) {
					$errorMessage = "Error: ".$e->getMessage()." in ".$e->getFile()." Line ".$e->getLine();
					$handler->logger->error($errorMessage."\n".$e->getTraceAsString());
					$handler->addMessage($errorMessage);
					$errors++;
				}
			}

			$handler->setProgress($progressAfterPlayer);
		}

		$resultHandler->handleSaveResults($saveResults, "RiotIds von Spielern");
		if ($errors > 0) $handler->addResultMessage("- $errors Fehler beim Update");

		$progressAfterTeam = ($i + 1) * $progressPerTeam;
		$handler->setProgress($progressAfterTeam);
	}
});