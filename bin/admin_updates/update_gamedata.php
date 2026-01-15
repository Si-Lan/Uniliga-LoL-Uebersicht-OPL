<?php
require_once dirname(__DIR__,2) . '/bootstrap.php';

use App\Domain\Enums\Jobs\UpdateJobAction;
use App\Domain\Enums\Jobs\UpdateJobType;
use App\Domain\Repositories\GameRepository;
use App\Service\JobHandler;
use App\Service\ResultHandler;
use App\Service\Updater\GameUpdater;

$handler = new JobHandler(UpdateJobType::ADMIN, UpdateJobAction::UPDATE_GAMEDATA);
$resultHandler = new ResultHandler($handler);

$handler->run(function(JobHandler $handler) use ($resultHandler) {
    $gameRepo = new GameRepository();
    if ($handler->tournamentContext !== null) {
        $games = $gameRepo->findAllWithoutDataByTournament($handler->tournamentContext);
    } else {
        $games = $gameRepo->findAllWithoutData();
    }

    $gameUpdater = new GameUpdater();
    $batchSize = 50;
    $delay = 10;

    $count = 0;
    $total = count($games);
    $batches = array_chunk($games, $batchSize);

    $handler->addMessage("Updating $total games in " . count($batches) . " Batches.");
    
    foreach ($batches as $i => $batch) {
        $batchNum = $i + 1;
        $handler->addMessage("Batch $batchNum:");

        foreach ($batch as $game) {
            $handler->addMessage("Updating $game->id");

            try {
                $saveResult = $gameUpdater->updateGameData($game->id);
                $resultHandler->handleSaveResult($saveResult->result, 'gamedata');
            } catch (\Exception $e) {
                $handler->addMessage("Error: " . $e->getMessage());
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