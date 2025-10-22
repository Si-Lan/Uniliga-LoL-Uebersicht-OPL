<?php

namespace App\Service\Updater;

use App\Domain\Repositories\PlayerInTeamInTournamentRepository;
use App\Domain\Repositories\PlayerInTeamRepository;
use App\Domain\Repositories\PlayerRepository;
use App\Domain\Repositories\TeamInTournamentRepository;
use App\Domain\Repositories\TeamRepository;
use App\Domain\Repositories\TournamentRepository;
use App\Service\OplApiService;
use App\Service\OplLogoService;

class TeamUpdater {
	private TeamRepository $teamRepo;
	private PlayerRepository $playerRepo;
	private OplApiService $oplApiService;
	private OplLogoService $oplLogoService;
	public function __construct() {
		$this->teamRepo = new TeamRepository();
		$this->playerRepo = new PlayerRepository();
		$this->oplApiService = new OplApiService();
		$this->oplLogoService = new OplLogoService();
	}

	/**
	 * @throws \Exception
	 */
	public function updateTeam(int $teamId): array {
		$team = $this->teamRepo->findById($teamId);
		if ($team === null) {
			throw new \Exception("Team not found", 404);
		}

		try {
			$oplTeam = $this->oplApiService->fetchFromEndpoint("team/$teamId/users");
		} catch (\Exception $e) {
			throw new \Exception("Failed to fetch data from OPL API: ".$e->getMessage(), 500);
		}

		$teamEntity = $this->teamRepo->createFromOplData($oplTeam);
		$teamSaveResult = $this->teamRepo->save($teamEntity, fromOplData: true);
		if ($teamSaveResult["team"]?->logoId !== null) {
			$lastLogoUpdate = $teamSaveResult["team"]->lastLogoDownload;
			$now = new \DateTimeImmutable();
			if ($lastLogoUpdate === null || $now->diff($lastLogoUpdate)->days > 7) {
				$logoDownload = $this->oplLogoService->downloadTeamLogo($teamEntity->id);
				$teamSaveResult["logoDownload"] = $logoDownload;
			}
			if (!array_key_exists("logoDownload", $teamSaveResult)) $teamSaveResult["logoDownload"] = null;
		}

		$oplPlayers = $oplTeam['users'];
		$oplPlayerIds = array_column($oplPlayers, 'ID');

		$playerInTeamRepo = new PlayerInTeamRepository();

		$playerSaveResults = [];
		$addedPlayers = [];
		$removedPlayers = [];
		foreach ($oplPlayers as $oplPlayer) {
			$playerEntity = $this->playerRepo->createFromOplData($oplPlayer);
			$playerSaveResult = $this->playerRepo->save($playerEntity, fromOplData: true);
			$playerSaveResults[] = $playerSaveResult;

			if ($playerSaveResult["player"]?->id !== null) {
				$addedToTeam = $playerInTeamRepo->addPlayerToTeam($playerSaveResult["player"]->id, $team->id);
				if ($addedToTeam) {
					$addedPlayers[] = $playerSaveResult["player"];
				}
			}
		}

		$playersCurrentlyInTeam = $playerInTeamRepo->findAllByTeamAndActiveStatus($team, active: true);
		foreach ($playersCurrentlyInTeam as $playerInTeam) {
			if (!in_array($playerInTeam->player->id, $oplPlayerIds)) {
				$playerInTeamRepo->removePlayerFromTeam($playerInTeam->player->id, $team->id);
				$removedPlayers[] = $playerInTeam->player;
			}
		}

		return ["team"=>$teamSaveResult, "players"=>$playerSaveResults, "addedPlayers"=>$addedPlayers, "removedPlayers"=>$removedPlayers];
	}

	/**
	 * @throws \Exception
	 */
	public function updatePlayers(int $teamId): array {
		$team = $this->teamRepo->findById($teamId);
		if ($team === null) {
			throw new \Exception("Team not found", 404);
		}

		try {
			$teamData = $this->oplApiService->fetchFromEndpoint("team/$teamId/users");
		} catch (\Exception $e) {
			throw new \Exception("Failed to fetch data from OPL API: ".$e->getMessage(), 500);
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

		return ['players' => $saveResults, 'addedPlayers' => $addedPlayers, 'removedPlayers' => $removedPlayers, 'tournamentChanges' => $tournamentAdditions];
	}
}