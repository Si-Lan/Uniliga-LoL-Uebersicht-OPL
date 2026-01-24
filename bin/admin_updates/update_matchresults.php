<?php
require_once dirname(__DIR__,2) . '/bootstrap.php';

use App\Domain\Enums\EventType;
use App\Domain\Enums\Jobs\UpdateJobAction;
use App\Domain\Enums\Jobs\UpdateJobType;
use App\Domain\Repositories\MatchupRepository;
use App\Service\JobHandler;
use App\Service\ResultHandler;
use App\Service\Updater\MatchupUpdater;

$handler = new JobHandler(
	UpdateJobType::ADMIN,
	UpdateJobAction::UPDATE_RESULTS
);
$resultHandler = new ResultHandler($handler);

$handler->run(function (JobHandler $handler) use ($resultHandler) {
	$options = getopt("j:", ["unplayed"]);
	$unplayedOnly = isset($options["unplayed"]);

	$matchupRepo = new MatchupRepository();

	if ($handler->tournamentContext->eventType === EventType::TOURNAMENT) {
		$matchups = $matchupRepo->findAllByRootTournament($handler->tournamentContext, $unplayedOnly);
	} elseif ($handler->tournamentContext->isStage()) {
		$matchups = $matchupRepo->findAllByTournamentStage($handler->tournamentContext, $unplayedOnly);
	} else {
		$matchups = $matchupRepo->findAllByParentTournament($handler->tournamentContext, $unplayedOnly);
	}

	if (count($matchups) === 0) {
		$handler->addMessageAndResult("Keine Matchups gefunden.");
		return;
	}

	$matchupUpdater = new MatchupUpdater();

	$matchupResults = [];
	$gameResults = [];
	$gameInMatchupResults = [];
	foreach ($matchups as $i=>$matchup) {
		if ($i !== 0) {
			usleep(500000);
			$handler->addMessage("");
		}
		$handler->addMessage("Aktualisiere Matchup-Ergebnis für ({$matchup->id})");

		try {
			$saveResult = $matchupUpdater->updateMatchupResults($matchup->id);

			$matchupResults[] = $saveResult["matchup"];
			$resultHandler->handleSaveResult($saveResult["matchup"]->result, "Matchup");

			foreach ($saveResult["games"] as $game) {
				$gameResults[] = $game;
				$resultHandler->handleSaveResult($game->result, "Spiel {$game->entity?->id}");
			}

			foreach ($saveResult["gamesInMatchup"] as $gameInMatchupResult) {
				$gameInMatchupResults[] = $gameInMatchupResult;
				$resultHandler->handleSaveResult($gameInMatchupResult->result, "Spiel {$gameInMatchupResult->entity?->game?->id} in Matchup");
			}

		} catch (\Exception $e) {
			$errorMessage = "Error: ". $e->getMessage()." in ".$e->getFile()." Line ".$e->getLine();
			$handler->logger->error($errorMessage."\n".$e->getTraceAsString());
			$handler->addMessage($errorMessage);
		}

		$handler->setProgress(($i + 1) / count($matchups) * 100);
	}

	$resultHandler->handleSaveResults($matchupResults, "Matchups");
	$resultHandler->handleSaveResults($gameResults, "LoL-Spiel");
	$resultHandler->handleSaveResults($gameInMatchupResults, "LoL-Spiel in Matchups");
});