<?php

namespace App\AjaxHandlers;

use App\Components\Standings\StandingsTable;
use App\Repositories\TeamRepository;
use App\Repositories\TournamentRepository;

class FragmentHandler {
	public function standingsTable(array $dataGet): void {
		$tournamentId = $dataGet['tournamentId'] ?? null;
		$teamId = $dataGet['teamId'] ?? null;

		if (is_null($tournamentId)) {
			http_response_code(400);
			echo 'Missing tournamentId';
			return;
		}

		$tournamentRepo = new TournamentRepository();
		$tournament = $tournamentRepo->findStandingsEventById($tournamentId);
		if (is_null($tournament)) {
			http_response_code(404);
			echo 'Tournament not found';
			return;
		}

		$teamRepo = new TeamRepository();
		$team = ($teamId) ? $teamRepo->findById($teamId) : null;

		$table = new StandingsTable($tournament,$team);

		echo $table->render();
	}
}