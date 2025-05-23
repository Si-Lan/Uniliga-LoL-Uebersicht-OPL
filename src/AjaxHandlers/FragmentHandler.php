<?php

namespace App\AjaxHandlers;

use App\Components\Cards\SummonerCard;
use App\Components\Games\GameDetails;
use App\Components\Matches\MatchButton;
use App\Components\Matches\MatchButtonList;
use App\Components\Matches\MatchHistory;
use App\Components\Standings\StandingsTable;
use App\Repositories\GameRepository;
use App\Repositories\MatchupRepository;
use App\Repositories\PlayerInTeamInTournamentRepository;
use App\Repositories\PlayerInTeamRepository;
use App\Repositories\TeamInTournamentRepository;
use App\Repositories\TeamRepository;
use App\Repositories\TournamentRepository;
use App\Utilities\DataParsingHelpers;
use App\Utilities\EntitySorter;

class FragmentHandler {
	use DataParsingHelpers;
	public function standingsTable(array $dataGet): void {
		$tournamentId = $this->IntOrNull($dataGet['tournamentId'] ?? null);
		$teamId = $this->IntOrNull($dataGet['teamId'] ?? null);

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
		$teamId = $this->intOrNull($dataGet['teamId'] ?? null);
		$tournamentId = $this->intOrNull($dataGet['tournamentId'] ?? null);

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

	public function matchButton(array $dataGet): void {
		$matchupId = $this->intOrNull($dataGet['matchupId'] ?? null);
		$teamId = $this->intOrNull($dataGet['teamId'] ?? null);

		if (is_null($matchupId)) {
			http_response_code(400);
			echo 'Missing matchupId';
			return;
		}

		$matchupRepo = new MatchupRepository();
		$matchup = $matchupRepo->findById($matchupId);
		if (is_null($matchup)) {
			http_response_code(404);
			echo 'Matchup not found';
			return;
		}

		$teamRepo = new TeamRepository();
		$team = ($teamId) ? $teamRepo->findById($teamId) : null;

		echo new MatchButton($matchup,$team);
	}

	public function matchButtonList(array $dataGet): void {
		$tournamentId = $this->intOrNull($dataGet['tournamentId'] ?? null);
		$teamId = $this->intOrNull($dataGet['teamId'] ?? null);

		if (is_null($tournamentId)) {
			http_response_code(400);
			echo 'Missing tournamentId';
			return;
		}

		$tournamentRepo = new TournamentRepository();
		$tournamentStage = $tournamentRepo->findById($tournamentId);
		if (is_null($tournamentStage)) {
			http_response_code(404);
			echo 'Tournament not found';
			return;
		}

		$teamInTournamentRepo = new TeamInTournamentRepository();
		$teamInTournament = ($teamId) ? $teamInTournamentRepo->findByTeamIdAndTournament($teamId, $tournamentStage->rootTournament) : null;

		echo new MatchButtonList($tournamentStage,$teamInTournament);
	}

	public function gameDetails(array $dataGet): void {
		$gameId = $this->stringOrNull($dataGet['gameId'] ?? null);
		$teamId = $this->intOrNull($dataGet['teamId'] ?? null);

		if (is_null($gameId)) {
			http_response_code(400);
			echo 'missing gameId';
			return;
		}
		$gameRepo = new GameRepository();
		$game = $gameRepo->findById($gameId);
		if (is_null($game)) {
			http_response_code(404);
			echo 'Game not found';
			return;
		}

		$teamRepo = new TeamRepository();
		$focusTeam = $teamId ? $teamRepo->findById($teamId) : null;

		echo new GameDetails($game, $focusTeam);
	}

	public function matchHistory(array $dataGet): void {
		$teamId = $this->intOrNull($dataGet['teamId'] ?? null);
		$tournamentStageId = $this->intOrNull($dataGet['tournamentStageId'] ?? null);

		if (is_null($teamId)) {
			http_response_code(400);
			echo 'missing teamId';
			return;
		}
		if (is_null($tournamentStageId)) {
			http_response_code(400);
			echo 'missing tournamentStageId';
			return;
		}

		$tournamentRepo = new TournamentRepository();
		$teamInTournamentRepo = new TeamInTournamentRepository();

		$tournamentStage = $tournamentRepo->findById($tournamentStageId);
		$teamInTournament = $teamInTournamentRepo->findByTeamIdAndTournament($teamId, $tournamentStage->rootTournament);

		echo new MatchHistory($teamInTournament, $tournamentStage);
	}
}