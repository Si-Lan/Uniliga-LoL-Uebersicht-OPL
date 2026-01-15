<?php
require_once dirname(__DIR__,2) . '/bootstrap.php';

use App\Domain\Enums\Jobs\UpdateJobAction;
use App\Domain\Enums\Jobs\UpdateJobType;
use App\Domain\Repositories\TeamInTournamentRepository;
use App\Domain\Repositories\TeamRepository;
use App\Service\JobHandler;
use App\Service\ResultHandler;
use App\Service\Updater\TeamUpdater;

$handler = new JobHandler(UpdateJobType::ADMIN, UpdateJobAction::UPDATE_TEAM_RANKS);
$resultHandler = new ResultHandler($handler);

$handler->run(function(JobHandler $handler) use ($resultHandler) {
	if ($handler->tournamentContext !== null) {
		$teamInTournamentRepo = new TeamInTournamentRepository();
		$teams = $teamInTournamentRepo->findAllByRootTournament($handler->tournamentContext);
		$teams = array_map(fn($team) => $team->team, $teams);
	} else {
		$teamRepo = new TeamRepository();
		$teams = $teamRepo->findAll();
	}

	$teamUpdater = new TeamUpdater();

	$count = 0;
	$total = count($teams);
	$handler->addMessage("Updating $total teams");
	foreach ($teams as $team) {
		$handler->addMessage("Updating ($team->id) $team->name");

		try {
			$saveResult = $teamUpdater->updateRank($team->id);
			$resultHandler->handleSaveResult($saveResult['team']->result, 'Rank');
			if ($saveResult['tournamentSeasonRanks'] !== null) {
				foreach ($saveResult['tournamentSeasonRanks'] as $tournamentSeasonRank) {
					$resultHandler->handleSaveResult($tournamentSeasonRank->result, 'Season Rank in a Tournament');
				}
			}
		} catch (\Exception $e) {
			$handler->addMessage("Error: ". $e->getMessage());
		}

		$count++;
		$handler->setProgress(($count / $total) * 100);
	}
});