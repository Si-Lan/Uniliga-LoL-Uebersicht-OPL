<?php

namespace App\Service\Updater;

use App\Domain\Entities\TeamSeasonRankInTournament;
use App\Domain\Entities\Tournament;
use App\Domain\Entities\ValueObjects\RankAverage;
use App\Domain\Entities\ValueObjects\RankMapper;
use App\Domain\Repositories\GameInMatchRepository;
use App\Domain\Repositories\PatchRepository;
use App\Domain\Repositories\PlayerInTeamInTournamentRepository;
use App\Domain\Repositories\PlayerInTeamRepository;
use App\Domain\Repositories\PlayerRepository;
use App\Domain\Repositories\RankedSplitRepository;
use App\Domain\Repositories\TeamInTournamentRepository;
use App\Domain\Repositories\TeamRepository;
use App\Domain\Repositories\TeamSeasonRankInTournamentRepository;
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

		$playerSaveResults = $this->updatePlayers($teamId, $oplTeam);

		return ["team"=>$teamSaveResult, "players"=>$playerSaveResults, "addedPlayers"=>$playerSaveResults['addedPlayers'], "removedPlayers"=>$playerSaveResults['removedPlayers']];
	}

	/**
	 * @throws \Exception
	 */
	public function updatePlayers(int $teamId, ?array $teamData = null): array {
		$team = $this->teamRepo->findById($teamId);
		if ($team === null) {
			throw new \Exception("Team not found", 404);
		}

		if ($teamData === null) {
			try {
				$teamData = $this->oplApiService->fetchFromEndpoint("team/$teamId/users");
			} catch (\Exception $e) {
				throw new \Exception("Failed to fetch data from OPL API: ".$e->getMessage(), 500);
			}
		}

		$oplPlayers = $teamData['users'];
		$ids = array_column($oplPlayers, 'ID');

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
			$playerEntity = $this->playerRepo->createFromOplData($oplPlayer);
			$saveResult = $this->playerRepo->save($playerEntity, fromOplData: true);
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

    /**
     * @throws \Exception
     */
    public function updateRank(int $teamId): array {
        $team = $this->teamRepo->findById($teamId);
        if ($team === null) {
            throw new \Exception("Team not found", 404);
        }

        $tournamentRepo = new TournamentRepository();
        $playerInTeamRepo = new PlayerInTeamRepository();


        // 1. Aktuelle Spieler im Team holen, avg Rank ausrechnen und im Team setzen
        $playersInTeam = $playerInTeamRepo->findAllByTeamAndActiveStatus($team, active: true);
        $playerRanks = [];
        foreach ($playersInTeam as $playerInTeam) {
            $rank = $playerInTeam->player->rank;
            if ($rank !== null && $rank->isRank()) {
                $playerRanks[] = $rank;
            }
        }

        if (count($playerRanks) > 0) {
            $rankValues = array_map(fn($rank) => RankMapper::getValue($rank), $playerRanks);
            $avgRankNum = array_sum($rankValues) / count($rankValues);
            $avgRank = RankMapper::fromValue($avgRankNum);
            $team->rank = new RankAverage($avgRank->rankTier, $avgRank->rankDiv, $avgRankNum);
        } else {
            $team->rank = new RankAverage("UNRANKED", null, 0);
        }
        $teamSaveResult = $this->teamRepo->save($team);

        // 2. Alle laufenden Turniere im laufenden RankedSplit holen
        $teamInTournamentRepo = new TeamInTournamentRepository();
        $playerInTeamInTournamentRepo = new PlayerInTeamInTournamentRepository();
        $rankedSplitRepo = new RankedSplitRepository();
        $currentRankedSplits = $rankedSplitRepo->findCurrentSplits();
        $currentRankedSplit = $currentRankedSplits[0]??null;
        $currentTournaments = $tournamentRepo->findAllRootTournamentsInCurrentRankedSplit();
        $currentTournaments = array_values(array_filter($currentTournaments, fn(Tournament $tournament) => !$tournament->finished));

        // 3. Für jedes Turnier Spieler im Team holen, avg Rank ausrechnen und als TeamSeasonRankInTournament setzen
        $teamSeasonRankInTournamentRepo = new TeamSeasonRankInTournamentRepository();
        $tournamentSaveResults = [];
        foreach ($currentTournaments as $tournament) {
            if (!$teamInTournamentRepo->isTeamInRootTournament($team->id, $tournament->id)) {
                continue;
            }

            $playersInTeamInTournament = $playerInTeamInTournamentRepo->findAllByTeamAndTournamentAndActiveStatus($team, $tournament, active: true);

            $tournamentPlayerRanks = [];
            foreach ($playersInTeamInTournament as $playerInTeamInTournament) {
                $rank = $playerInTeamInTournament->player->rank;
                if ($rank !== null && $rank->isRank()) {
                    $tournamentPlayerRanks[] = $rank;
                }
            }

            if (count($tournamentPlayerRanks) > 0) {
                $rankValues = array_map(fn($rank) => RankMapper::getValue($rank), $tournamentPlayerRanks);
                $avgRankNum = array_sum($rankValues) / count($rankValues);
                $avgRank = RankMapper::fromValue($avgRankNum);
                $teamSeasonRankinTournamentAverage = new RankAverage($avgRank->rankTier, $avgRank->rankDiv, $avgRankNum);
                $teamSeasonRankInTournament = new TeamSeasonRankInTournament($team, $tournament, $currentRankedSplit, $teamSeasonRankinTournamentAverage);
            } else {
                $teamSeasonRankInTournament = new TeamSeasonRankInTournament($team, $tournament, $currentRankedSplit, new RankAverage("UNRANKED", null, 0));
            }

            $tournamentSaveResults[] = $teamSeasonRankInTournamentRepo->save($teamSeasonRankInTournament);
        }

        return ['team'=>$teamSaveResult, 'tournamentSeasonRanks'=>$tournamentSaveResults];
    }

    /**
     * @param int $teamId
     * @param int $tournamentId
     * @return array
     * @throws \Exception
     */
    public function updateStats(int $teamId, int $tournamentId): array {
        $team = $this->teamRepo->findById($teamId);
        if ($team === null) {
            throw new \Exception("Team not found", 404);
        }
        $tournamentRepo = new TournamentRepository();
        $tournament = $tournamentRepo->findById($tournamentId);
        if ($tournament === null) {
            throw new \Exception("Tournament not found", 404);
        }
        $teamInTournamentRepo = new TeamInTournamentRepository();
        $teamInTournament = $teamInTournamentRepo->findByTeamAndTournament($team, $tournament);
        if ($teamInTournament === null) {
            throw new \Exception("Team not in tournament", 404);
        }

        $patchRepo = new PatchRepository();
        $latestPatch = $patchRepo->findLatestPatchWithAllData();
        $championData = $latestPatch->getChampionData();


        // setup stat arrays
        $champsPlayed = [];
        $champsBanned = [];
        $champsPlayedAgainst = [];
        $champsBannedAgainst = [];
        $gamesPlayed = 0;
        $gamesWon = 0;
        $winTime = 0;
        $avgWinTime = 0;

        $gameInMatchRepo = new GameInMatchRepository();
        $gamesInMatches = $gameInMatchRepo->findAllByTeamInTournament($teamInTournament);
		$checkedGameIds = [];
        foreach ($gamesInMatches as $gameInMatch) {
            $game = $gameInMatch->game;
			if (in_array($game->id, $checkedGameIds)) continue;
			$checkedGameIds[] = $game->id;
			if ($game->gameData === null) continue;
            if ($gameInMatch->blueTeam->team->id === $team->id) {
                $teamData = $game->gameData->blueTeam;
                $playerData = $game->gameData->blueTeamPlayers;
                $opponentData = $game->gameData->redTeam;
                $opponentPlayerData = $game->gameData->redTeamPlayers;
            } elseif ($gameInMatch->redTeam->team->id === $team->id) {
                $teamData = $game->gameData->redTeam;
                $playerData = $game->gameData->redTeamPlayers;
                $opponentData = $game->gameData->blueTeam;
                $opponentPlayerData = $game->gameData->blueTeamPlayers;
            } else {
                continue;
            }

            $gamesPlayed++;

            // Wins and WinTime
            if ($teamData->win) {
                $gamesWon++;
                $winTime += $game->gameData->gameDurationSeconds;
            }

            // played Champions
            foreach ($playerData as $player) {
                $champion = $player->championName;
                if (!array_key_exists($champion, $champsPlayed)) {
                    $champsPlayed[$champion] = ['games' => 1, 'wins' => $teamData->win ? 1 : 0];
                } else {
                    $champsPlayed[$champion]['games']++;
                    if ($teamData->win) {
                        $champsPlayed[$champion]['wins']++;
                    }
                }
            }
            // played Champions Against
            foreach ($opponentPlayerData as $player) {
                $champion = $player->championName;
                if (!array_key_exists($champion, $champsPlayedAgainst)) {
                    $champsPlayedAgainst[$champion] = 1;
                } else {
                    $champsPlayedAgainst[$champion]++;
                }
            }
            // banned Champions
            foreach ($teamData->bans as $ban) {
                $champion = $championData[$ban];
                if (!array_key_exists($champion, $champsBanned)) {
                    $champsBanned[$champion] = 1;
                } else {
                    $champsBanned[$champion]++;
                }
            }
            // banned Champions Against
            foreach ($opponentData->bans as $ban) {
                $champion = $championData[$ban];
                if (!array_key_exists($champion, $champsBannedAgainst)) {
                    $champsBannedAgainst[$champion] = 1;
                } else {
                    $champsBannedAgainst[$champion]++;
                }
            }
        }

        if ($gamesWon > 0) {
            $avgWinTime = $winTime / $gamesWon;
        }

        $teamInTournament->champsPlayed = $champsPlayed;
        $teamInTournament->champsBanned = $champsBanned;
        $teamInTournament->champsPlayedAgainst = $champsPlayedAgainst;
        $teamInTournament->champsBannedAgainst = $champsBannedAgainst;
        $teamInTournament->gamesPlayed = $gamesPlayed;
        $teamInTournament->gamesWon = $gamesWon;
        $teamInTournament->avgWinTime = (int) $avgWinTime;

        $saveResult = $teamInTournamentRepo->saveStats($teamInTournament);

        return $saveResult;
    }

    /**
     * @param int $teamId
     * @param int $tournamentId
     * @return array
     * @throws \Exception
     */
    public function updatePlayerStats(int $teamId, int $tournamentId): array {
        $teamInTournamentRepo = new TeamInTournamentRepository();
        $teamInTournament = $teamInTournamentRepo->findByTeamIdAndTournamentId($teamId, $tournamentId);
        if ($teamInTournament === null) {
            throw new \Exception("Team not in tournament", 404);
        }
        $playerInTeamInTournamentRepo = new PlayerInTeamInTournamentRepository();
        $playersInTournament = $playerInTeamInTournamentRepo->findAllByTeamInTournament($teamInTournament);

        $playerUpdater = new PlayerUpdater();
        $playerUpdateResults = [];
        foreach ($playersInTournament as $playerInTournament) {
            $playerUpdateResults = $playerUpdater->updateStats($playerInTournament->player->id, $tournamentId);
        }

        return $playerUpdateResults;
    }
}