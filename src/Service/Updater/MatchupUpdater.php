<?php

namespace App\Service\Updater;

use App\Domain\Enums\SaveResult;
use App\Domain\Repositories\GameInMatchRepository;
use App\Domain\Repositories\GameRepository;
use App\Domain\Repositories\MatchupRepository;
use App\Service\OplApiService;

class MatchupUpdater {
	private MatchupRepository $matchupRepo;
	private OplApiService $oplApiService;
	public function __construct() {
		$this->matchupRepo = new MatchupRepository();
		$this->oplApiService = new OplApiService();
	}

	/**
	 * @throws \Exception
	 */
	public function updateMatchupResults(int $matchupId): array {
		$matchup = $this->matchupRepo->findById($matchupId);
		if ($matchup === null) {
			throw new \Exception("Matchup not found", 404);
		}

		try {
			$matchupData = $this->oplApiService->fetchFromEndpoint("matchup/$matchupId/result,statistics");
		} catch (\Exception $e) {
			throw new \Exception("Failed to fetch data from OPL API: ".$e->getMessage(), 500);
		}

		$oplMatchupResults = $matchupData['result'];
		$scores = $oplMatchupResults['scores'];
		$winIDs = $oplMatchupResults['win_IDs'];
		$lossIDs = $oplMatchupResults['loss_IDs'];
		$drawIDs = $oplMatchupResults['draw_IDs'];
		$defWin = $oplMatchupResults['defwin'];

		$matchup->team1Score = strval($scores[$matchup->team1?->team->id]) ?? null;
		$matchup->team2Score = strval($scores[$matchup->team2?->team->id]) ?? null;
		$matchup->played = $matchupData['state_key'] >= 4;
		$matchup->winnerId = count($winIDs) > 0 ? $winIDs[0] : null;
		$matchup->loserId = count($lossIDs) > 0 ? $lossIDs[0] : null;
		$matchup->draw = $matchup->played && count($drawIDs) > 0;
		$matchup->defWin = $matchup->played && count($defWin) > 0;

		$matchupSaveResult = $this->matchupRepo->save($matchup);


		$oplGames = $matchupData['statistics'];
		if ($oplGames === null) {
			return ['matchup' => $matchupSaveResult, 'games' => [], 'gamesInMatchup' => []];
		}

		$gameSaveResults = [];
		$gameToMatchResults = [];
		$gameRepo = new GameRepository();
		$gameInMatchRepo = new GameInMatchRepository();
		foreach ($oplGames as $i=>$oplGame) {
			$gameEntity = $gameRepo->createEmptyFromId($oplGame['metadata']['matchId']);
			$gameSaveResult = $gameRepo->save($gameEntity);
			$gameSaveResults[] = $gameSaveResult;

			if ($gameSaveResult['result'] === SaveResult::FAILED) {
				continue;
			}

			$matchResultSegments = $oplMatchupResults['result_segments'];
			$blueTeamWin = $oplGame['info']['teams'][0]['win'];
			if ($blueTeamWin) {
				$blueTeamId = intval($matchResultSegments[$i]['win_IDs'][0]);
				$redTeamId = intval($matchResultSegments[$i]['loss_IDs'][0]);
			} else {
				$blueTeamId = intval($matchResultSegments[$i]['loss_IDs'][0]);
				$redTeamId = intval($matchResultSegments[$i]['win_IDs'][0]);
			}

			$blueTeam = match ($blueTeamId) {
				$matchup->team1->team->id => $matchup->team1->team,
				$matchup->team2->team->id => $matchup->team2->team,
				default => null,
			};
			$redTeam = match ($redTeamId) {
				$matchup->team1->team->id => $matchup->team1->team,
				$matchup->team2->team->id => $matchup->team2->team,
				default => null,
			};

			$gameInMatchEntity = $gameInMatchRepo->createFromEntities($gameEntity, $matchup, $blueTeam, $redTeam, oplConfirmed: true);
			$gameToMatchResult = $gameInMatchRepo->save($gameInMatchEntity);
			$gameToMatchResults[] = $gameToMatchResult;
		}

		return ['matchup' => $matchupSaveResult, 'games' => $gameSaveResults, 'gamesInMatchup' => $gameToMatchResults];
	}
}