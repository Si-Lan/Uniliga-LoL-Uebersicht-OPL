<?php

namespace App\Service\Updater;

use App\Domain\Enums\SaveResult;
use App\Domain\Repositories\GameRepository;
use App\Domain\ValueObjects\RepositorySaveResult;
use App\Service\RiotApiService;

class GameUpdater {
    private GameRepository $gameRepo;
    private RiotApiService $riotApiService;
    public function __construct() {
        $this->gameRepo = new GameRepository();
        $this->riotApiService = new RiotApiService();
    }

    /**
     * @param string $gameId
     * @return RepositorySaveResult
     * @throws \Exception
     */
    public function updateGameData(string $gameId): RepositorySaveResult {
        $game = $this->gameRepo->findById($gameId);
        if ($game === null) {
            throw new \Exception("Game not found", 404);
        }
        if ($game->rawMatchdata !== null) {
            throw new \Exception("Game already has rawMatchdata", 200);
        }

        $riotApiResponse = $this->riotApiService->getMatchByMatchId($game->id);
        if (!$riotApiResponse->isSuccess()) {
			return new RepositorySaveResult(
				SaveResult::FAILED,
				entity: $game,
				additionalData: ['error'=>$riotApiResponse->getError(), 'httpCode'=>$riotApiResponse->getStatusCode()]
			);
        }

        $data = $riotApiResponse->getData();
        $data = $this->shortenMatchData($data);
        $game->rawMatchdata = $data;
		return $this->gameRepo->save($game);
    }

    private function shortenMatchData(array $matchData): array {
        $save_values = [
            "assists",
            "champLevel",
            "championId",
            "championName",
            "championTransform",
            "deaths",
            "gameEndedInEarlySurrender",
            "gameEndedInSurrender",
            "goldEarned",
            "individualPosition",
            "item0",
            "item1",
            "item2",
            "item3",
            "item4",
            "item5",
            "item6",
            "kills",
            "lane",
            "participantId",
            "perks",
            "profileIcon",
            "puuid",
            "riotIdName",
            "riotIdGameName",
            "riotIdTagline",
            "role",
            "summoner1Id",
            "summoner2Id",
            "summonerId",
            "summonerLevel",
            "summonerName",
            "teamId",
            "teamPosition",
            "totalMinionsKilled",
            "visionScore",
            "win",
        ];
        foreach ($matchData["info"]["participants"] as $pid=>$participant) {
            unset($matchData["info"]["participants"][$pid]["challenges"]);
            foreach ($participant as $key=>$value) {
                if (!in_array($key, $save_values)) {
                    unset($matchData["info"]["participants"][$pid][$key]);
                }
            }
        }
        return $matchData;
    }
}