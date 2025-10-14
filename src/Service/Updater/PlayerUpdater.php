<?php

namespace App\Service\Updater;

use App\Domain\Entities\Player;
use App\Domain\Entities\Team;
use App\Domain\Enums\SaveResult;
use App\Domain\Repositories\PlayerInTeamRepository;
use App\Domain\Repositories\PlayerRepository;
use App\Domain\Repositories\TeamRepository;
use App\Service\OplApiService;

class PlayerUpdater {
	private PlayerRepository $playerRepo;
	private OplApiService $oplApiService;

	public function __construct() {
		$this->playerRepo = new PlayerRepository();
		$this->oplApiService = new OplApiService();
	}

	/**
	 * @return array{'result': SaveResult, 'changes': ?array<string, mixed>, 'previous': ?array<string,mixed>, 'player': ?Player}
	 * @throws \Exception
	 */
	public function updatePlayerAccount(int $playerId): array {
		$player = $this->playerRepo->findById($playerId);
		if ($player === null) {
			throw new \Exception("Player not found", 404);
		}

		try {
			$playerData = $this->oplApiService->fetchFromEndpoint("user/$playerId/launcher");
		} catch (\Exception $e) {
			throw new \Exception("Failed to fetch data from OPL API: ".$e->getMessage(), 500);
		}

		$oplPlayerAccounts = $playerData['launcher'];

		if (!array_key_exists('13', $oplPlayerAccounts)) {
			throw new \Exception("Player does not have a linked Riot Account", 200);
		}

		$player->setRiotIdFromString($oplPlayerAccounts['13']['last_known_username']);
		return $this->playerRepo->save($player);
	}

	/**
	 * @return array{'players': array{'result': SaveResult, 'changes': ?array<string, mixed>, 'previous': ?array<string,mixed>, 'player': ?Player}, 'errors': ?array{'player': Player, 'error': string}, 'team': Team}
	 * @throws \Exception
	 */
	public function updatePlayerAccountsForTeam(int $teamId): array {
		$teamRepo = new TeamRepository();
		$team = $teamRepo->findById($teamId);
		if ($team === null) {
			throw new \Exception("Team not found", 404);
		}
		$playerInTeamRepo = new PlayerInTeamRepository();
		$playersInTeam = $playerInTeamRepo->findAllByTeamAndActiveStatus($team, active: true);

		$saveResults = [];
		$errors = [];
		foreach ($playersInTeam as $playerInTeam) {
			try {
				$saveResult = $this->updatePlayerAccount($playerInTeam->player->id);
				$saveResults[] = $saveResult;
			} catch (\Exception $e) {
				$errors[] = ['player'=>$playerInTeam->player, 'error'=>$e->getMessage()];
			}
			sleep(1);
		}

		return ['players' => $saveResults, 'errors' => $errors, 'team' => $team];
	}
}