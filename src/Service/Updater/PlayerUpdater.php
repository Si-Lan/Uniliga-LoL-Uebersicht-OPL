<?php

namespace App\Service\Updater;

use App\Domain\Entities\Player;
use App\Domain\Entities\Team;
use App\Domain\Enums\SaveResult;
use App\Domain\Repositories\PlayerInTeamRepository;
use App\Domain\Repositories\PlayerRepository;
use App\Domain\Repositories\TeamRepository;
use App\Service\OplApiService;
use App\Service\RiotApiService;

class PlayerUpdater {
	private PlayerRepository $playerRepo;
	private OplApiService $oplApiService;
	private RiotApiService $riotApiService;

	public function __construct() {
		$this->playerRepo = new PlayerRepository();
		$this->oplApiService = new OplApiService();
		$this->riotApiService = new RiotApiService();
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

	/**
	 * @return array{'result': SaveResult, 'changes': ?array<string, mixed>, 'previous': ?array<string,mixed>, 'player': ?Player}
	 * @throws \Exception
	 */
	public function updatePuuidByRiotId(int $playerId): array {
		$player = $this->playerRepo->findById($playerId);
		if ($player === null) {
			throw new \Exception("Player not found", 404);
		}
		if ($player->riotIdName === null || $player->riotIdTag === null) {
			throw new \Exception("Player does not have a linked Riot Account", 200);
		}

		$riotApiResponse = $this->riotApiService->getRiotAccountByRiotId($player->riotIdName, $player->riotIdTag);

		if (!$riotApiResponse->isSuccess()) {
			return ['result'=>SaveResult::FAILED, 'httpCode'=> $riotApiResponse->getStatusCode(), 'changes'=>null, 'previous'=>null, 'player'=>$player];
		}

		$puuid = $riotApiResponse->getData()['puuid'];
		$player->puuid = $puuid;
		$saveResult = $this->playerRepo->save($player);
		return ['result'=>$saveResult['result'], 'changes'=>$saveResult['changes']??null, 'previous'=>$saveResult['previous']??null, 'player'=>$player];
	}

    /**
     * @param int $playerId
     * @return array{'result': SaveResult, 'changes': ?array<string, mixed>, 'previous': ?array<string,mixed>, 'player': ?Player}
     * @throws \Exception
     */
    public function updateRiotIdByPuuid(int $playerId): array {
        $player = $this->playerRepo->findById($playerId);
        if ($player === null) {
            throw new \Exception("Player not found", 404);
        }
        if ($player->puuid === null) {
            throw new \Exception("Player does not have a set PUUID", 200);
        }

        $riotApiResponse = $this->riotApiService->getRiotAccountByPuuid($player->puuid);

        if (!$riotApiResponse->isSuccess()) {
            return ['result'=>SaveResult::FAILED, 'error'=>$riotApiResponse->getError(), 'httpCode'=>$riotApiResponse->getStatusCode(), 'changes'=>null, 'previous'=>null, 'player'=>$player];
        }

        $gameName = $riotApiResponse->getData()['gameName'];
        $tagLine = $riotApiResponse->getData()['tagLine'];
        $player->riotIdName = $gameName;
        $player->riotIdTag = $tagLine;
        $saveResult = $this->playerRepo->save($player);
        return $saveResult;
    }
}