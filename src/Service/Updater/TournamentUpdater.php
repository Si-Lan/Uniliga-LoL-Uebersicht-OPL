<?php

namespace App\Service\Updater;

use App\Domain\Repositories\MatchupRepository;
use App\Domain\Repositories\TournamentRepository;
use App\Service\OplApiService;

class TournamentUpdater {
	private TournamentRepository $tournamentRepo;
	private OplApiService $oplApiService;
	public function __construct() {
		$this->tournamentRepo = new TournamentRepository();
		$this->oplApiService = new OplApiService();
	}

	/**
	 * @throws \Exception
	 */
	public function updateMatchups(int $tournamentId): array {
		$tournament = $this->tournamentRepo->findById($tournamentId);
		if ($tournament === null) {
			throw new \Exception("Tournament not found", 404);
		}

		if (!$tournament->isStage()) {
			throw new \Exception("Tournament is not a stage with Matchups", 400);
		}

		try {
			$tournamentData = $this->oplApiService->fetchFromEndpoint("tournament/$tournamentId/matches");
		} catch (\Exception $e) {
			throw new \Exception("Failed to fetch data from OPL API: ".$e->getMessage(), 500);
		}

		$oplMatchups = $tournamentData['matches'];
		$ids = array_column($oplMatchups, 'ID');

		$matchupRepo = new MatchupRepository();

		$saveResults = [];
		foreach ($oplMatchups as $oplMatchup) {
			$matchupEntity = $matchupRepo->createFromOplData($oplMatchup);
			$saveResult = $matchupRepo->save($matchupEntity, fromOplData: true);
			$saveResults[] = $saveResult;
		}

		$matchupsCurrentlyInTournament = $matchupRepo->findAllByTournamentStage($tournament);
		$removedMatchups = [];
		foreach ($matchupsCurrentlyInTournament as $matchup) {
			if (!in_array($matchup->id, $ids) && !$matchup->played) {
				$matchupRepo->delete($matchup);
				$removedMatchups[] = $matchup;
			}
		}

		return ['matchups'=>$saveResults, 'removedMatchups'=>$removedMatchups];
	}
}