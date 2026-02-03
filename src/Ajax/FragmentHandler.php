<?php

namespace App\Ajax;

use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Repositories\GameInMatchRepository;
use App\Domain\Repositories\GameRepository;
use App\Domain\Repositories\MatchupRepository;
use App\Domain\Repositories\PlayerInTeamInTournamentRepository;
use App\Domain\Repositories\PlayerInTeamRepository;
use App\Domain\Repositories\PlayerRepository;
use App\Domain\Repositories\TeamInTournamentRepository;
use App\Domain\Repositories\TeamRepository;
use App\Domain\Repositories\TournamentRepository;
use App\Domain\Services\EntitySorter;
use App\UI\Components\Cards\SummonerCard;
use App\UI\Components\EliminationBrackets\EliminationBracket;
use App\UI\Components\EloList\EloLists;
use App\UI\Components\Games\GameDetails;
use App\UI\Components\Matches\MatchButton;
use App\UI\Components\Matches\MatchButtonList;
use App\UI\Components\Matches\MatchHistory;
use App\UI\Components\MultiOpggButton;
use App\UI\Components\Player\PlayerOverview;
use App\UI\Components\Player\PlayerSearchCard;
use App\UI\Components\Popups\MatchPopupContent;
use App\UI\Components\Popups\TeamPopupContent;
use App\UI\Components\Standings\StandingsTable;
use App\UI\Enums\EloListView;
use App\UI\Page\AssetManager;

class FragmentHandler {
	use DataParsingHelpers;
	private function sendJsonFragment(string $html): void {
		echo json_encode([
			'html' => $html,
			'js' => AssetManager::getJsFiles(),
			'css' => AssetManager::getCssFiles()
		]);
	}
	private function sendJsonError(string $message, int $code): void {
		http_response_code($code);
		echo json_encode(['error'=>$message]);
	}
	public function standingsTable(array $dataGet): void {
		$tournamentId = $this->IntOrNull($dataGet['tournamentId'] ?? null);
		$teamId = $this->IntOrNull($dataGet['teamId'] ?? null);

		if (is_null($tournamentId)) {
			$this->sendJsonError('Missing tournamentId',400);
			return;
		}

		$tournamentRepo = new TournamentRepository();
		$tournament = $tournamentRepo->findStandingsEventById($tournamentId);
		if (is_null($tournament)) {
			$this->sendJsonError('Tournament not found',404);
			return;
		}

		$teamRepo = new TeamRepository();
		$team = ($teamId) ? $teamRepo->findById($teamId) : null;

		$table = new StandingsTable($tournament,$team);

		$this->sendJsonFragment($table->render());
	}

	public function summonerCards(array $dataGet): void {
		$teamId = $this->intOrNull($dataGet['teamId'] ?? null);
		$tournamentId = $this->intOrNull($dataGet['tournamentId'] ?? null);

		if (is_null($teamId)) {
			$this->sendJsonError('Missing teamId',400);
			return;
		}
		$teamRepo = new TeamRepository();
		$team = $teamRepo->findById($teamId);
		if (is_null($team)) {
			$this->sendJsonError('Team not found',404);
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

		$this->sendJsonFragment("<div class='summoner-card-container'>$summonerCardHtml</div>");
	}
	public function multiOpggButton(array $dataGet): void {
		$teamId = $this->intOrNull($dataGet['teamId'] ?? null);
		$tournamentId = $this->intOrNull($dataGet['tournamentId'] ?? null);
		if (is_null($teamId) || is_null($tournamentId)) {
			$this->sendJsonError('Missing teamId or tournamentId',400);
		}
		$teamInTournamentRepo = new TeamInTournamentRepository();
		$teamInTournament = $teamInTournamentRepo->findByTeamIdAndTournamentId($teamId, $tournamentId);
		if (is_null($teamInTournament)) {
			$this->sendJsonError('Team not found in tournament',404);
		}

		$this->sendJsonFragment(new MultiOpggButton($teamInTournament));
	}

	public function matchButton(array $dataGet): void {
		$matchupId = $this->intOrNull($dataGet['matchupId'] ?? null);
		$teamId = $this->intOrNull($dataGet['teamId'] ?? null);
		$popupId = $this->stringOrNull($dataGet['popupId'] ?? null);

		if (is_null($matchupId)) {
			$this->sendJsonError('Missing matchupId',400);
			return;
		}

		$matchupRepo = new MatchupRepository();
		$matchup = $matchupRepo->findById($matchupId);
		if (is_null($matchup)) {
			$this->sendJsonError('Matchup not found',404);
			return;
		}

		$teamRepo = new TeamRepository();
		$team = ($teamId) ? $teamRepo->findById($teamId) : null;

		$this->sendJsonFragment(new MatchButton($matchup,$team, injectedPopupId: $popupId));
	}

	public function matchButtonList(array $dataGet): void {
		$tournamentId = $this->intOrNull($dataGet['tournamentId'] ?? null);
		$teamId = $this->intOrNull($dataGet['teamId'] ?? null);

		if (is_null($tournamentId)) {
			$this->sendJsonError('Missing tournamentId',400);
			return;
		}

		$tournamentRepo = new TournamentRepository();
		$tournamentStage = $tournamentRepo->findById($tournamentId);
		if (is_null($tournamentStage)) {
			$this->sendJsonError('Tournament not found',404);
			return;
		}

		$teamInTournamentRepo = new TeamInTournamentRepository();
		$teamInTournament = ($teamId) ? $teamInTournamentRepo->findByTeamIdAndTournament($teamId, $tournamentStage->rootTournament) : null;

		$this->sendJsonFragment(new MatchButtonList($tournamentStage,$teamInTournament));
	}

	public function gameDetails(array $dataGet): void {
		$gameId = $this->stringOrNull($dataGet['gameId'] ?? null);
		$teamId = $this->intOrNull($dataGet['teamId'] ?? null);
		$matchupId = $this->intOrNull($dataGet['matchupId'] ?? null);

		if (is_null($gameId)) {
			$this->sendJsonError('Missing gameId',400);
			return;
		}
		$gameRepo = new GameRepository();
		$game = $gameRepo->findById($gameId);
		if (is_null($game)) {
			$this->sendJsonError('Game not found',404);
			return;
		}

		$teamRepo = new TeamRepository();
		$focusTeam = $teamId ? $teamRepo->findById($teamId) : null;

		$gameInMatch = null;
		if (!is_null($matchupId)) {
			$gameInMatchRepo = new GameInMatchRepository();
			$gameInMatch = $gameInMatchRepo->findByGameIdAndMatchupId($gameId, $matchupId);
			if (is_null($gameInMatch)) {
				$this->sendJsonError('GameInMatch not found for given matchupId',404);
				return;
			}
		}

		$gameParameter = $gameInMatch ?? $game;

		$this->sendJsonFragment(new GameDetails($gameParameter, $focusTeam));
	}

	public function matchHistory(array $dataGet): void {
		$teamId = $this->intOrNull($dataGet['teamId'] ?? null);
		$tournamentStageId = $this->intOrNull($dataGet['tournamentStageId'] ?? null);

		if (is_null($teamId)) {
			$this->sendJsonError('Missing teamId',400);
			return;
		}
		if (is_null($tournamentStageId)) {
			$this->sendJsonError('Missing tournamentStageId',400);
			return;
		}

		$tournamentRepo = new TournamentRepository();
		$teamInTournamentRepo = new TeamInTournamentRepository();

		$tournamentStage = $tournamentRepo->findById($tournamentStageId);
		$teamInTournament = $teamInTournamentRepo->findByTeamIdAndTournament($teamId, $tournamentStage->rootTournament);

		$this->sendJsonFragment(new MatchHistory($teamInTournament, $tournamentStage));
	}

	public function playerOverview(array $dataGet): void {
		$playerId = $this->intOrNull($dataGet['playerId'] ?? null);

		if (is_null($playerId)) {
			$this->sendJsonError('Missing playerId',400);
			return;
		}
		$playerRepo = new PlayerRepository();
		$player = $playerRepo->findById($playerId);
		if (is_null($player)) {
			$this->sendJsonError('Player not found',404);
			return;
		}

		$this->sendJsonFragment(new PlayerOverview($player));
	}

	public function playerSearchCardsByRecents(array $dataGet): void {
		$playerIds = $this->decodeJsonOrDefault($dataGet['playerIds'] ?? null);

		if (count($playerIds) == 0) {
			$this->sendJsonError('no playerIds given',400);
			return;
		}

		$playerRepo = new PlayerRepository();
		$players = $playerRepo->findAllByIds($playerIds);

		$indexedPlayers = [];
		foreach ($players as $player) {
			$indexedPlayers[$player->id] = $player;
		}

		$html = '';
		foreach ($playerIds as $playerId) {
			$html .= new PlayerSearchCard($indexedPlayers[$playerId], true);
		}
		$this->sendJsonFragment($html);
	}
	public function playerSearchCardsBySearch(array $dataGet): void {
		$searchString = $this->stringOrNull($dataGet['search'] ?? null);

		if (is_null($searchString)) {
			$this->sendJsonError('missing Search String',400);
			return;
		}

		$playerRepo = new PlayerRepository();
		$players = $playerRepo->findAllByNameContains($searchString);

		$html = '';
		foreach ($players as $player) {
			$html .= new PlayerSearchCard($player);
		}
		$this->sendJsonFragment($html);
	}

	public function eloLists(array $dataGet): void {
		$tournamentId = $this->intOrNull($dataGet['tournamentId'] ?? null);
		$view = $this->stringOrNull($dataGet['view'] ?? null);

		$view = EloListView::tryFrom($view);

		if (is_null($tournamentId)) {
			$this->sendJsonError('missing tournamentId',400);
			return;
		}
		if (is_null($view)) {
			$this->sendJsonError('missing view',400);
			return;
		}

		$tournamentRepo = new TournamentRepository();
		$tournament = $tournamentRepo->findById($tournamentId);
		if (is_null($tournament)) {
			$this->sendJsonError('Tournament not found',404);
			return;
		}

		$this->sendJsonFragment(new EloLists($tournament,$view));
	}

	public function teamPopup(array $dataGet): void {
		$teamId = $this->intOrNull($dataGet['teamId'] ?? null);
		$tournamentId = $this->intOrNull($dataGet['tournamentId'] ?? null);
		if (is_null($teamId)) {
			$this->sendJsonError('missing teamId',400);
			return;
		}
		if (is_null($tournamentId)) {
			$this->sendJsonError('missing tournamentId',400);
			return;
		}

		$teamInTournamentRepo = new TeamInTournamentRepository();
		$teamInTournament = $teamInTournamentRepo->findByTeamIdAndTournamentId($teamId, $tournamentId);
		if (is_null($teamInTournament)) {
			$this->sendJsonError('Team not found',404);
			return;
		}

		$this->sendJsonFragment(new TeamPopupContent($teamInTournament));
	}
	public function matchPopup(array $dataGet): void {
		$matchId = $this->intOrNull($dataGet['matchId'] ?? null);
		$teamId = $this->intOrNull($dataGet['teamId'] ?? null);
		if (is_null($matchId)) {
			$this->sendJsonError('missing matchId',400);
			return;
		}

		$matchupRepo = new MatchupRepository();
		$matchup = $matchupRepo->findById($matchId);
		if (is_null($matchup)) {
			$this->sendJsonError('Match not found',404);
			return;
		}
		$teamRepo = new TeamRepository();
		$team = $teamId ? $teamRepo->findById($teamId) : null;

		$this->sendJsonFragment(new MatchPopupContent($matchup, $team));
	}

	public function eventStageView(array $dataGet): void {
		$tournamentId = $this->intOrNull($dataGet['tournamentId'] ?? null);
		$teamId = $this->intOrNull($dataGet['teamId'] ?? null);

		if (is_null($tournamentId)) {
			$this->sendJsonError('missing tournamentId',400);
			return;
		}

		$tournamentRepo = new TournamentRepository();
		$tournamentStage = $tournamentRepo->findStandingsEventById($tournamentId);
		if (is_null($tournamentStage)) {
			$this->sendJsonError('Tournament not found',404);
			return;
		}

		$teamInTournamentRepo = new TeamInTournamentRepository();
		$teamInTournament = $teamId ? $teamInTournamentRepo->findByTeamIdAndTournament($teamId,$tournamentStage->getRootTournament()) : null;

		if ($tournamentStage->isEventWithEliminationBracket()) {
			$eliminationBracket = new EliminationBracket($tournamentStage, $teamInTournament->team);
			$content = $eliminationBracket->render();
		} else {
			$standings = new StandingsTable($tournamentStage, $teamInTournament->team);
			$matchList = new MatchButtonList($tournamentStage, $teamInTournament);
			$content = $standings->render() . $matchList->render();
		}

		$this->sendJsonFragment($content);
	}
}