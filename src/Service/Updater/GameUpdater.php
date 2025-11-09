<?php

namespace App\Service\Updater;

use App\Domain\Entities\Game;
use App\Domain\Enums\SaveResult;
use App\Domain\Repositories\GameRepository;
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
     * @return array{'result': SaveResult, 'error': ?string, 'httpCode': int, 'changes': ?array<string, mixed>, 'previous': ?array<string,mixed>, 'game': ?Game}
     * @throws \Exception
     */
    public function updateGameData(string $gameId): array {
        $game = $this->gameRepo->findById($gameId);
        if ($game === null) {
            throw new \Exception("Game not found", 404);
        }
        if ($game->rawMatchdata !== null) {
            throw new \Exception("Game already has rawMatchdata", 200);
        }

        $riotApiResponse = $this->riotApiService->getMatchByMatchId($game->id);
        if (!$riotApiResponse->isSuccess()) {
            return ['result'=>SaveResult::FAILED, 'error'=>$riotApiResponse->getError(), 'httpCode'=>$riotApiResponse->getStatusCode(), 'changes'=>null, 'previous'=>null, 'game'=>$game];
        }

        $data = $riotApiResponse->getData();
        $data = $this->shortenMatchData($data);
        $game->rawMatchdata = $data;
        $saveResult = $this->gameRepo->save($game);
        return ['result'=>$saveResult['result'], 'changes'=>$saveResult['changes'], 'previous'=>$saveResult['previous'], 'game'=>$game];
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