<?php

namespace App\Service\Updater;

use App\Domain\Entities\Team;
use App\Domain\Repositories\MatchupRepository;
use App\Domain\Repositories\TeamInTournamentStageRepository;
use App\Domain\Repositories\TeamRepository;
use App\Domain\Repositories\TournamentRepository;
use App\Service\OplApiService;
use App\Service\OplLogoService;

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
	public function updateTeams(int $tournamentId): array {
		$tournament = $this->tournamentRepo->findById($tournamentId);
		if ($tournament === null) {
			throw new \Exception("Tournament not found", 404);
		}
		if (!$tournament->isStage()) {
			throw new \Exception("Tournament is not a stage with Teams", 400);
		}

		try {
			$tournamentData = $this->oplApiService->fetchFromEndpoint("tournament/$tournamentId/team_registrations");
		} catch (\Exception $e) {
			throw new \Exception("Failed to fetch data from OPL API: ".$e->getMessage(), 500);
		}

		$oplTeams = $tournamentData['team_registrations'];
		$ids = array_column($oplTeams, 'ID');

		$teamRepo = new TeamRepository();
		$teamInTournamentStageRepo = new TeamInTournamentStageRepository();
		$oplLogoService = new OplLogoService();

		$saveResults = [];
		$addedTeams = [];
		foreach ($oplTeams as $oplTeam) {
			$teamEntity = $teamRepo->createFromOplData($oplTeam);
			$saveResult = $teamRepo->save($teamEntity, fromOplData: true);
			if (array_key_exists("team", $saveResult) && $saveResult["team"] instanceof Team && $saveResult["team"]->logoId !== null) {
				$lastLogoUpdate = $saveResult["team"]->lastLogoDownload;
				$now = new \DateTimeImmutable();
				if ($lastLogoUpdate === null || $now->diff($lastLogoUpdate)->days > 7) {
					$logoDownload = $oplLogoService->downloadTeamLogo($teamEntity->id);
					$saveResult["logoDownload"] = $logoDownload;
				}
			}
			if (!array_key_exists("logoDownload", $saveResult)) $saveResult["logoDownload"] = null;
			$saveResults[] = $saveResult;

			$addedToTournament = $teamInTournamentStageRepo->addTeamToTournamentStage($teamEntity->id, $tournament->id);
			if ($addedToTournament) {
				$addedTeams[] = $saveResult["team"];
			}
		}

		$teamsCurrentlyInTournament = $teamInTournamentStageRepo->findAllByTournamentStage($tournament);
		$removedTeams = [];
		foreach ($teamsCurrentlyInTournament as $team) {
			if (!in_array($team->team->id, $ids)) {
				$teamInTournamentStageRepo->removeTeamFromTournamentStage($team->team->id, $tournament->id);
				$removedTeams[] = $team->team;
			}
		}

		return ['teams'=>$saveResults,'removedTeams'=>$removedTeams,'addedTeams'=>$addedTeams];
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