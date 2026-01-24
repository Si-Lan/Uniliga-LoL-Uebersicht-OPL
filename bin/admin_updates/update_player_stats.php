<?php
require_once dirname(__DIR__,2) . '/bootstrap.php';

use App\Domain\Enums\Jobs\UpdateJobAction;
use App\Domain\Enums\Jobs\UpdateJobType;
use App\Domain\Repositories\PlayerInTournamentRepository;
use App\Domain\Repositories\PlayerRepository;
use App\Service\JobHandler;
use App\Service\ResultHandler;
use App\Service\Updater\PlayerUpdater;

$handler = new JobHandler(UpdateJobType::ADMIN, UpdateJobAction::UPDATE_PLAYER_STATS);
$resultHandler = new ResultHandler($handler);

$handler->run(function(JobHandler $handler) use ($resultHandler) {
	if ($handler->tournamentContext !== null) {
		$playerInTournamentRepo = new PlayerInTournamentRepository();
		$players = $playerInTournamentRepo->findAllByTournament($handler->tournamentContext);
		$players = array_map(fn($player) => $player->player, $players);
	} else {
		$playerRepo = new PlayerRepository();
		$players = $playerRepo->findAll();
	}

	$playerUpdater = new PlayerUpdater();

	$count = 0;
	$total = count($players);
	$handler->addMessage("Updating $total players");
	foreach ($players as $player) {
		$handler->addMessage("Updating ($player->id) $player->name");

		try {
			$saveResult = $playerUpdater->updateStats($player->id, $handler->tournamentContext->id);
			$resultHandler->handleSaveResult($saveResult['playerInTournament']->result, "Player-Stats");
			if (count($saveResult['playerInTeamsInTournament']) > 0) {
				foreach ($saveResult['playerInTeamsInTournament'] as $playerInTeamInTournament) {
					$resultHandler->handleSaveResult($playerInTeamInTournament->result, "Stats in a Team");
				}
			}
		} catch (\Exception $e) {
			$handler->addMessage("Error: ". $e->getMessage());
			$handler->logger->error("Error: ". $e->getMessage(). "\n".$e->getTraceAsString());
		}

		$count++;
		$handler->setProgress(($count / $total) * 100);
	}
});