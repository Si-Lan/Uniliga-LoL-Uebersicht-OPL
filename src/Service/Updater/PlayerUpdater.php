<?php

namespace App\Service\Updater;

use App\Domain\Entities\Player;
use App\Domain\Entities\PlayerSeasonRank;
use App\Domain\Entities\Team;
use App\Domain\Entities\ValueObjects\RankForPlayer;
use App\Domain\Enums\SaveResult;
use App\Domain\Repositories\GameInMatchRepository;
use App\Domain\Repositories\PlayerInTeamInTournamentRepository;
use App\Domain\Repositories\PlayerInTeamRepository;
use App\Domain\Repositories\PlayerInTournamentRepository;
use App\Domain\Repositories\PlayerRepository;
use App\Domain\Repositories\PlayerSeasonRankRepository;
use App\Domain\Repositories\TeamRepository;
use App\Domain\Repositories\TournamentRepository;
use App\Service\OplApiService;
use App\Service\RiotApiService;

class PlayerUpdater {
	private PlayerRepository $playerRepo;
	private OplApiService $oplApiService;
	private RiotApiService $riotApiService;
    private PlayerSeasonRankRepository $playerSeasonRankRepo;

	public function __construct() {
		$this->playerRepo = new PlayerRepository();
		$this->oplApiService = new OplApiService();
		$this->riotApiService = new RiotApiService();
        $this->playerSeasonRankRepo = new PlayerSeasonRankRepository();
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

    /**
     * @param int $playerId
     * @return array{
     *     'player': array{'result': SaveResult, 'changes': ?array<string, mixed>, 'previous': ?array<string,mixed>, 'player': ?Player},
     *     'playerSeasonRank': ?array{'result': SaveResult, 'changes': ?array<string, mixed>, 'previous': ?array<string,mixed>, 'playerSeasonRank': ?PlayerSeasonRank}
     *     }
     * @throws \Exception
     */
    public function updateRank(int $playerId): array {
        $player = $this->playerRepo->findById($playerId);
        if ($player === null) {
            throw new \Exception("Player not found", 404);
        }
        if ($player->puuid === null) {
            throw new \Exception("Player does not have a set PUUID", 200);
        }

        $riotApiResponse = $this->riotApiService->getRankByPuuid($player->puuid);

        if (!$riotApiResponse->isSuccess()) {
            return ['player'=>['result'=>SaveResult::FAILED, 'error'=>$riotApiResponse->getError(), 'httpCode'=>$riotApiResponse->getStatusCode(), 'previous'=>null, 'player'=>$player], 'playerSeasonRank'=>null];
        }

        $rankedQueues = array_column($riotApiResponse->getData(), null, 'queueType');

        if (array_key_exists('RANKED_SOLO_5x5', $rankedQueues)) {
            $soloRanked = $rankedQueues['RANKED_SOLO_5x5'];
            $playerRank = new RankForPlayer($soloRanked['tier'], $soloRanked['rank'], $soloRanked['leaguePoints']);
        } else {
            $playerRank = new RankForPlayer('UNRANKED', null, null);
        }

        // Update Rank for Player
        $player->rank = $playerRank;
        $playerSaveResult = $this->playerRepo->save($player);

        // Update Rank for Player in currently running RankedSplit
        $playerSeasonRankRepo = new PlayerSeasonRankRepository();
        $playerSeasonRank = $playerSeasonRankRepo->findCurrentSeasonRankForPlayer($player);
        if ($playerSeasonRank === null) {
            return ['player'=>$playerSaveResult, 'playerSeasonRank'=>null];
        }
        $playerSeasonRank->rank = $playerRank;
        $playerSeasonSaveResult = $this->playerSeasonRankRepo->save($playerSeasonRank);

        return ['player'=>$playerSaveResult, 'playerSeasonRank'=>$playerSeasonSaveResult];
    }

    /**
     * @param int $playerId
     * @param int $tournamentId
     * @return array{playerInTournament: array, playerInTeamsInTournament: array}
     * @throws \Exception
     */
    public function updateStats(int $playerId, int $tournamentId): array {
        $player = $this->playerRepo->findById($playerId);
        if ($player === null) {
            throw new \Exception("Player not found", 404);
        }
        $tournamentRepo = new TournamentRepository();
        $tournament = $tournamentRepo->findById($tournamentId);
        if ($tournament === null) {
            throw new \Exception("Tournament not found", 404);
        }
        $playerInTournamentRepo = new PlayerInTournamentRepository();
        $playerInTournament = $playerInTournamentRepo->findByPlayerIdAndTournamentId($player->id, $tournament->id);
        if ($playerInTournament === null) {
            throw new \Exception("Player not in tournament", 404);
        }

        $playerInTeamInTournamentRepo = new PlayerInTeamInTournamentRepository();
        $playerInTeamsInTournament = $playerInTeamInTournamentRepo->findAllByPlayerAndTournament($player, $tournament);

        $allRoles = ["top","jungle","middle","bottom","utility"];
        $roles = ["top"=>0,"jungle"=>0,"middle"=>0,"bottom"=>0,"utility"=>0];
        $champions = [];

        $gameInMatchRepo = new GameInMatchRepository();

        $statTeamsTournamentResults = [];
        foreach ($playerInTeamsInTournament as $playerInTeamInTournament) {
            $rolesInTeam = ["top"=>0,"jungle"=>0,"middle"=>0,"bottom"=>0,"utility"=>0];
            $championsInTeam = [];

            $gamesInMatches = $gameInMatchRepo->findAllByTeamInTournament($playerInTeamInTournament->teamInTournament);
			$checkedGameIds = [];
            foreach ($gamesInMatches as $gameInMatch) {
                $game = $gameInMatch->game;
				if (in_array($game->id, $checkedGameIds)) continue;
				$checkedGameIds[] = $game->id;
				if ($game->gameData === null) continue;
                $allPlayerData = array_merge($game->gameData->blueTeamPlayers, $game->gameData->redTeamPlayers);
                $allPlayerPuuids = array_map(fn($playerData)=> $playerData->puuid, $allPlayerData);

                if (!in_array($player->puuid, $allPlayerPuuids)) continue;

                $bluePlayerPuuids = array_map(fn($playerData)=> $playerData->puuid, $game->gameData->blueTeamPlayers);
                $teamPlayerData = in_array($player->puuid, $bluePlayerPuuids) ? $game->gameData->blueTeamPlayers : $game->gameData->redTeamPlayers;
                $teamPlayerPuuids = array_map(fn($playerData)=> $playerData->puuid, $teamPlayerData);

                $allPlayerDataKey = array_search($player->puuid, $allPlayerPuuids);
                $playerData = $allPlayerData[$allPlayerDataKey];

                $position = strtolower($playerData->teamPosition??"");

                if ($position === "") {
                    $teamRoles = array_map(fn($playerData) => strtolower($playerData->teamPosition), $teamPlayerData);
                    $teamRoles = array_filter($teamRoles, fn($role) => $role !== "");
                    $possibleRoles = array_values(array_diff($allRoles, $teamRoles));
                    if (count($possibleRoles) == 1) {
                        $position = $possibleRoles[0];
                    } else {
                        $teamPlayerDataKey = array_search($player->puuid, $teamPlayerPuuids);
                        $position = $allRoles[$teamPlayerDataKey];
                    }
                }

                $rolesInTeam[$position]++;
                $roles[$position]++;
                $champion = $playerData->championName;
                foreach ([&$champions, &$championsInTeam] as &$championsRef) {
                    if (!array_key_exists($champion, $championsRef)) {
                        $championsRef[$champion] = [
                            'games' => 1,
                            'wins' => ($playerData->win ? 1 : 0),
                            'kills' => $playerData->kills,
                            'deaths' => $playerData->deaths,
                            'assists' => $playerData->assists,
                        ];
                    } else {
                        $championsRef[$champion]['games']++;
                        $championsRef[$champion]['wins'] += ($playerData->win ? 1 : 0);
                        $championsRef[$champion]['kills'] += $playerData->kills;
                        $championsRef[$champion]['deaths'] += $playerData->deaths;
                        $championsRef[$champion]['assists'] += $playerData->assists;
                    }
                }
                unset($championsRef);
            }

            $playerInTeamInTournament->stats->roles = $rolesInTeam;
            $playerInTeamInTournament->stats->champions = $championsInTeam;
            $statTeamsTournamentResults[] = $playerInTeamInTournamentRepo->saveStats($playerInTeamInTournament);
        }

        $playerInTournament->stats->roles = $roles;
        $playerInTournament->stats->champions = $champions;
        $statTournamentResult = $playerInTournamentRepo->saveStats($playerInTournament);

        return [
            "playerInTournament" => $statTournamentResult,
            "playerInTeamsInTournament" => $statTeamsTournamentResults,
        ];
    }
}