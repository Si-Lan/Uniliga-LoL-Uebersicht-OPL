<?php
require_once dirname(__DIR__,2) . '/bootstrap.php';

use App\Domain\Enums\Jobs\UpdateJobAction;
use App\Domain\Enums\Jobs\UpdateJobType;
use App\Domain\Repositories\PlayerInTournamentRepository;
use App\Domain\Repositories\PlayerRepository;
use App\Service\JobHandler;
use App\Service\ResultHandler;
use App\Service\Updater\PlayerUpdater;

$handler = new JobHandler(UpdateJobType::ADMIN, UpdateJobAction::UPDATE_PLAYER_RANKS);
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

	$batchSize = 50;
	$delay = 10;

	$count = 0;
	$total = count($players);
	$batches = array_chunk($players, $batchSize);

	$handler->addMessage("Updating $total players in ".count($batches)." Batches.");
	foreach ($batches as $i=>$batch) {
		$batchNum = $i+1;
		$handler->addMessage("Batch $batchNum:");

		foreach ($batch as $player) {
			$handler->addMessage("Updating ($player->id) $player->name");

			try {
				$saveResult = $playerUpdater->updateRank($player->id);
				$resultHandler->handleSaveResult($saveResult['player']->result, "Rank");
				if ($saveResult['playerSeasonRank'] !== null) {
					$resultHandler->handleSaveResult($saveResult['playerSeasonRank']->result, "Season Rank");
				}
			} catch (\Exception $e) {
				$handler->addMessage("Error: ". $e->getMessage());
			}

			$count++;
			$handler->setProgress(($count / $total) * 100);
		}
		$handler->addMessage("finished batch $batchNum\n");

		if ($batchNum < count($batches)) {
			sleep($delay);
		}
	}
});