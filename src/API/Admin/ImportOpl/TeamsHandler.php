<?php

namespace App\API\Admin\ImportOpl;

use App\API\AbstractHandler;
use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Repositories\PlayerInTeamInTournamentRepository;
use App\Domain\Repositories\PlayerInTeamRepository;
use App\Domain\Repositories\PlayerRepository;
use App\Domain\Repositories\TeamInTournamentRepository;
use App\Domain\Repositories\TeamRepository;
use App\Domain\Repositories\TournamentRepository;
use App\Service\OplApiService;
use App\Service\Updater\PlayerUpdater;

class TeamsHandler extends AbstractHandler {
	use DataParsingHelpers;

	public function postTeamsPlayers(int $teamId): void {
		$this->checkRequestMethod('POST');

		$teamRepo = new TeamRepository();
		$team = $teamRepo->findById($teamId);
		if ($team === null) {
			$this->sendErrorResponse(404, 'Team not found');
		}

		$oplApi = new OplApiService();
		try {
			$teamData = $oplApi->fetchFromEndpoint("team/$teamId/users");
		} catch (\Exception $e) {
			$this->sendErrorResponse(500, 'Failed to fetch data from OPL API: ' . $e->getMessage());
		}

		$oplPlayers = $teamData['users'];
		$ids = array_column($oplPlayers, 'ID');

		$playerRepo = new PlayerRepository();
		$playerInTeamRepo = new PlayerInTeamRepository();
		$playerinTeamInTournamentRepo = new PlayerInTeamInTournamentRepository();
		$tournamentRepo = new TournamentRepository();
		$teamInTournamentRepo = new TeamInTournamentRepository();

		$activeTournaments = $tournamentRepo->findAllRunningRootTournaments();

		$tournamentAdditions = [];
		foreach ($activeTournaments as $i=>$tournament) {
			if (!$teamInTournamentRepo->isTeamInRootTournament($teamId, $tournament->id)) {
				unset($activeTournaments[$i]);
				continue;
			}
			$tournamentAdditions[$tournament->id] = ['tournament' => $tournament, 'addedPlayers' => [], 'removedPlayers' => []];
		}
		$activeTournaments = array_values($activeTournaments);

		$saveResults = [];
		$addedPlayers = [];
		foreach ($oplPlayers as $oplPlayer) {
			// Spieler speichern
			$playerEntity = $playerRepo->createFromOplData($oplPlayer);
			$saveResult = $playerRepo->save($playerEntity, fromOplData: true);
			$saveResult["result"] = $saveResult["result"]->name;
			$saveResults[] = $saveResult;

			// Spieler in Team eintragen
			$addedToTeam = $playerInTeamRepo->addPlayerToTeam($playerEntity->id, $team->id);
			if ($addedToTeam) {
				$addedPlayers[] = $saveResult["player"];
			}

			// Spieler in Team in aktive Turniere eintragen
			foreach ($activeTournaments as $tournament) {
				$addedToTeamInTournament = $playerinTeamInTournamentRepo->addPlayerToTeamInTournament($playerEntity->id, $team->id, $tournament->id);
				if ($addedToTeamInTournament) {
					$tournamentAdditions[$tournament->id]['addedPlayers'][] = $saveResult["player"];
				}
			}
		}

		// Spieler in Team inaktiv setzen
		$playersCurrentlyInTeam = $playerInTeamRepo->findAllByTeamAndActiveStatus($team, active: true);
		$removedPlayers = [];
		foreach ($playersCurrentlyInTeam as $playerInTeam) {
			if (!in_array($playerInTeam->player->id, $ids)) {
				$playerInTeamRepo->removePlayerFromTeam($playerInTeam->player->id, $team->id);
				$removedPlayers[] = $playerInTeam->player;
			}
		}

		// Spieler in Team in aktiven Turnieren inaktiv setzen
		foreach ($activeTournaments as $tournament) {
			$playersCurrentlyInTeamInTournament = $playerinTeamInTournamentRepo->findAllByTeamAndTournamentAndActiveStatus($team, $tournament, active: true);
			foreach ($playersCurrentlyInTeamInTournament as $playerInTeamInTournament) {
				if (!in_array($playerInTeamInTournament->player->id, $ids)) {
					$playerinTeamInTournamentRepo->removePlayerFromTeamInTournament($playerInTeamInTournament->player->id, $team->id, $tournament->id);
					$tournamentAdditions[$tournament->id]['removedPlayers'][] = $playerInTeamInTournament->player;
				}
			}
		}
		$tournamentAdditions = array_values($tournamentAdditions);

		echo json_encode(['players' => $saveResults, 'addedPlayers' => $addedPlayers, 'removedPlayers' => $removedPlayers, 'tournamentChanges' => $tournamentAdditions]);
	}

	public function postTeamsPlayersAccounts(int $teamId): void {
		$this->checkRequestMethod('POST');

		$playerUpdater = new PlayerUpdater();

		try {
			$saveResult = $playerUpdater->updatePlayerAccountsForTeam($teamId);
		} catch (\Exception $e) {
			$this->sendErrorResponse($e->getCode(), $e->getMessage());
		}

		echo json_encode($saveResult);
	}
}