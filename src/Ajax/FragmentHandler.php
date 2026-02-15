<?php

namespace App\Ajax;

use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Entities\Game;
use App\Domain\Enums\SuggestionStatus;
use App\Domain\Factories\GameInMatchFactory;
use App\Domain\Repositories\GameInMatchRepository;
use App\Domain\Repositories\GameRepository;
use App\Domain\Repositories\MatchupChangeSuggestionRepository;
use App\Domain\Repositories\MatchupRepository;
use App\Domain\Repositories\PlayerInTeamInTournamentRepository;
use App\Domain\Repositories\PlayerInTeamRepository;
use App\Domain\Repositories\PlayerRepository;
use App\Domain\Repositories\TeamInTournamentRepository;
use App\Domain\Repositories\TeamRepository;
use App\Domain\Repositories\TournamentRepository;
use App\Domain\Services\EntitySorter;
use App\Service\RiotApiService;
use App\Service\Updater\GameUpdater;
use App\UI\Components\Cards\SummonerCard;
use App\UI\Components\EliminationBrackets\EliminationBracket;
use App\UI\Components\EloList\EloLists;
use App\UI\Components\Games\GameDetails;
use App\UI\Components\Matches\ChangeSuggestions\AddSuggestionPopupContent;
use App\UI\Components\Matches\ChangeSuggestions\GameSuggestionDetails;
use App\UI\Components\Matches\MatchButton;
use App\UI\Components\Matches\MatchButtonList;
use App\UI\Components\Matches\MatchHistory;
use App\UI\Components\MultiOpggButton;
use App\UI\Components\Navigation\Header\NotificationSuggestionList;
use App\UI\Components\Player\PlayerOverview;
use App\UI\Components\Player\PlayerSearchCard;
use App\UI\Components\Popups\MatchPopupContent;
use App\UI\Components\Popups\TeamPopupContent;
use App\UI\Components\Standings\StandingsTable;
use App\UI\Enums\EloListView;

class FragmentHandler extends AbstractFragmentHandler {
	use DataParsingHelpers;
	public function standingsTable(array $dataGet): void {
		$tournamentId = $this->IntOrNull($dataGet['tournamentId'] ?? null);
		$teamId = $this->IntOrNull($dataGet['teamId'] ?? null);

		if (is_null($tournamentId)) {
			$this->sendJsonError('Missing tournamentId',400);
		}

		$tournamentRepo = new TournamentRepository();
		$tournament = $tournamentRepo->findStandingsEventById($tournamentId);
		if (is_null($tournament)) {
			$this->sendJsonError('Tournament not found',404);
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
		}
		$teamRepo = new TeamRepository();
		$team = $teamRepo->findById($teamId);
		if (is_null($team)) {
			$this->sendJsonError('Team not found',404);
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
		}

		$matchupRepo = new MatchupRepository();
		$matchup = $matchupRepo->findById($matchupId);
		if (is_null($matchup)) {
			$this->sendJsonError('Matchup not found',404);
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
		}

		$tournamentRepo = new TournamentRepository();
		$tournamentStage = $tournamentRepo->findById($tournamentId);
		if (is_null($tournamentStage)) {
			$this->sendJsonError('Tournament not found',404);
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
		}
		$gameRepo = new GameRepository();
		$game = $gameRepo->findById($gameId);
		if (is_null($game)) {
			$this->sendJsonError('Game not found',404);
		}

		$teamRepo = new TeamRepository();
		$focusTeam = $teamId ? $teamRepo->findById($teamId) : null;

		$gameInMatch = null;
		if (!is_null($matchupId)) {
			$gameInMatchRepo = new GameInMatchRepository();
			$gameInMatch = $gameInMatchRepo->findByGameIdAndMatchupId($gameId, $matchupId);
			if (is_null($gameInMatch)) {
				$this->sendJsonError('GameInMatch not found for given matchupId',404);
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
		}
		if (is_null($tournamentStageId)) {
			$this->sendJsonError('Missing tournamentStageId',400);
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
		}
		$playerRepo = new PlayerRepository();
		$player = $playerRepo->findById($playerId);
		if (is_null($player)) {
			$this->sendJsonError('Player not found',404);
		}

		$this->sendJsonFragment(new PlayerOverview($player));
	}

	public function playerSearchCardsByRecents(array $dataGet): void {
		$playerIds = $this->decodeJsonOrDefault($dataGet['playerIds'] ?? null);

		if (count($playerIds) == 0) {
			$this->sendJsonError('no playerIds given',400);
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
		}
		if (is_null($view)) {
			$this->sendJsonError('missing view',400);
		}

		$tournamentRepo = new TournamentRepository();
		$tournament = $tournamentRepo->findById($tournamentId);
		if (is_null($tournament)) {
			$this->sendJsonError('Tournament not found',404);
		}

		$this->sendJsonFragment(new EloLists($tournament,$view));
	}

	public function teamPopup(array $dataGet): void {
		$teamId = $this->intOrNull($dataGet['teamId'] ?? null);
		$tournamentId = $this->intOrNull($dataGet['tournamentId'] ?? null);
		if (is_null($teamId)) {
			$this->sendJsonError('missing teamId',400);
		}
		if (is_null($tournamentId)) {
			$this->sendJsonError('missing tournamentId',400);
		}

		$teamInTournamentRepo = new TeamInTournamentRepository();
		$teamInTournament = $teamInTournamentRepo->findByTeamIdAndTournamentId($teamId, $tournamentId);
		if (is_null($teamInTournament)) {
			$this->sendJsonError('Team not found',404);
		}

		$this->sendJsonFragment(new TeamPopupContent($teamInTournament));
	}
	public function matchPopup(array $dataGet): void {
		$matchId = $this->intOrNull($dataGet['matchId'] ?? null);
		$teamId = $this->intOrNull($dataGet['teamId'] ?? null);
		if (is_null($matchId)) {
			$this->sendJsonError('missing matchId',400);
		}

		$matchupRepo = new MatchupRepository();
		$matchup = $matchupRepo->findById($matchId);
		if (is_null($matchup)) {
			$this->sendJsonError('Match not found',404);
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
		}

		$tournamentRepo = new TournamentRepository();
		$tournamentStage = $tournamentRepo->findStandingsEventById($tournamentId);
		if (is_null($tournamentStage)) {
			$this->sendJsonError('Tournament not found',404);
		}

		$teamInTournamentRepo = new TeamInTournamentRepository();
		$teamInTournament = $teamId ? $teamInTournamentRepo->findByTeamIdAndTournament($teamId,$tournamentStage->getRootTournament()) : null;

		if ($tournamentStage->isEventWithEliminationBracket()) {
			$eliminationBracket = new EliminationBracket($tournamentStage, $teamInTournament?->team);
			$content = $eliminationBracket->render();
		} else {
			$standings = new StandingsTable($tournamentStage, $teamInTournament?->team);
			$matchList = new MatchButtonList($tournamentStage, $teamInTournament);
			$content = $standings->render() . $matchList->render();
		}

		$this->sendJsonFragment($content);
	}

	public function gameSuggestions(array $dataGet): void {
		$matchupId = $this->stringOrNull($dataGet['matchupId'] ?? null);
		$playerId = $this->intOrNull($dataGet['playerId'] ?? null);

		if (is_null($matchupId)) {
			$this->sendJsonError('missing matchupId',400);
		}
		if (is_null($playerId)) {
			$this->sendJsonError('missing playerId',400);
		}

		$matchupRepo = new MatchupRepository();
		$playerRepo = new PlayerRepository();
		$matchup = $matchupRepo->findById($matchupId);
		$player = $playerRepo->findById($playerId);
		if (is_null($matchup) || is_null($player)) {
			$this->sendJsonError('Matchup or Player not found',404);
		}

		$riotApiService = new RiotApiService();
		$apiResult = $riotApiService->getMatchIdsByPuuidAndDatetimeForTourneyGames($player->puuid, $matchup->plannedDate);

		if (!$apiResult->isSuccess()) {
			$this->sendJsonError('Anfrage an Riot API fehlgeschlagen',500);
		}

		$gameRepo = new GameRepository();
		$gameInMatchRepo = new GameInMatchRepository();
		$gameInMatchFactory = new GameInMatchFactory();

		$gameIds = $apiResult->getData();

		$htmlContent = "";
		foreach ($gameIds as $gameId) {
			$game = $gameRepo->findById($gameId);
			if ($game !== null) {
				$gameInMatch = $gameInMatchRepo->findByGameIdAndMatchupId($gameId, $matchupId);
				if ($gameInMatch === null) {
					$gameInMatch = $gameInMatchFactory->createFromEntitiesAndImplyTeams($game, $matchup);
				}
				if ($game->gameData !== null) {
					$htmlContent .= new GameSuggestionDetails($gameInMatch);
					continue;
				}
			}
			$gameApiResult = $riotApiService->getMatchByMatchId($gameId);
			if ($gameApiResult->isRateLimitExceeded()) {
				$htmlContent .= new GameSuggestionDetails(errorMessage: "Riot API Rate Limit - versuche es später noch einmal");
				continue;
			}
			if (!$gameApiResult->isSuccess()) {
				$htmlContent .= new GameSuggestionDetails(errorMessage: "Fehler beim Abrufen der Spieldaten von Riot API");
				continue;
			}

			$rawMatchData = $gameApiResult->getData();
			$matchData = GameUpdater::shortenMatchData($rawMatchData);
			$game = new Game($gameId, $matchData, null);
			$gameRepo->save($game);

			$gameInMatch = $gameInMatchFactory->createFromEntitiesAndImplyTeams($game, $matchup);
			$htmlContent .= new GameSuggestionDetails($gameInMatch);
		}

		if (empty($htmlContent)) {
			$htmlContent = "<span> Keine Spiele gefunden </span>";
		}

		$this->sendJsonFragment($htmlContent);
	}

	public function addSuggestionPopupContent(array $dataGet): void {
		$matchupId = $this->stringOrNull($dataGet['matchupId'] ?? null);
		if ($matchupId === null) {
			$this->sendJsonError('missing matchupId',400);
		}

		$matchupRepo = new MatchupRepository();
		$matchup = $matchupRepo->findById($matchupId);
		if (is_null($matchup)) {
			$this->sendJsonError('Matchup not found',404);
		}

		$this->sendJsonFragment(new AddSuggestionPopupContent($matchup));
	}

	public function notificationSuggestionList(array $dataGet): void {
		$matchupSuggestionRepo = new MatchupChangeSuggestionRepository();
		$matchupChangeSuggestions = $matchupSuggestionRepo->findAllByStatus(SuggestionStatus::PENDING);
		$this->sendJsonFragment(new NotificationSuggestionList($matchupChangeSuggestions));
	}
}