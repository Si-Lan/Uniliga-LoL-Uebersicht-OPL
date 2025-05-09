<?php

namespace App\AjaxHandlers;

use App\Components\Cards\SummonerCard;
use App\Components\Standings\StandingsTable;
use App\Entities\PlayerInTeamInTournament;
use App\Repositories\PlayerInTeamInTournamentRepository;
use App\Repositories\PlayerInTeamRepository;
use App\Repositories\TeamRepository;
use App\Repositories\TournamentRepository;
use App\Utilities\EntitySorter;

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

	public function summonerCards(array $dataGet): void {
		$teamId = $dataGet['teamId'] ?? null;
		$tournamentId = $dataGet['tournamentId'] ?? null;

		if (is_null($teamId)) {
			http_response_code(400);
			echo 'Missing teamId';
			return;
		}
		$teamRepo = new TeamRepository();
		$team = $teamRepo->findById($teamId);
		if (is_null($team)) {
			http_response_code(404);
			echo 'Team not found';
			return;
		}

		$tournamentRepo = new TournamentRepository();
		$tournament = ($tournamentId) ? $tournamentRepo->findById($tournamentId) : null;

		$playerInTeamRepo = new PlayerInTeamRepository();
		$playerInTeamInTournamentRepo = new PlayerInTeamInTournamentRepository();

		if (is_null($tournament)) {
			$playersInTeam = $playerInTeamRepo->findAllByTeam($team);
		} else {
			$playersInTeam = $playerInTeamInTournamentRepo->findAllByTeamAndTournament($team,$tournament);
			$playersInTeam = EntitySorter::sortPlayersByAllRoles($playersInTeam);
		}

		$summonerCardHtml = '';
		foreach ($playersInTeam as $playerInTeam) {
			$summonerCardHtml .= new SummonerCard($playerInTeam);
		}
		echo "<div class='summoner-card-container'>$summonerCardHtml</div>";

	}
}