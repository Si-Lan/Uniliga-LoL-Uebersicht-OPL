<?php

namespace App\Service\Updater;

use App\Domain\Entities\Matchup;
use App\Domain\Entities\Team;
use App\Domain\Repositories\MatchupRepository;
use App\Domain\Repositories\TeamInTournamentStageRepository;
use App\Domain\Repositories\TeamRepository;
use App\Domain\Repositories\TournamentRepository;
use App\Domain\ValueObjects\RepositorySaveResult;
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
	 * @return array{teams: array<RepositorySaveResult>, removedTeams: array<Team>, addedTeams: array<Team>}
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

			$logoDownload = null;
			if ($saveResult->entity instanceof Team && $saveResult->entity->logoId !== null) {
				$lastLogoUpdate = $saveResult->entity->lastLogoDownload;
				$now = new \DateTimeImmutable();
				if ($lastLogoUpdate === null || $now->diff($lastLogoUpdate)->days > 7) {
					$logoDownload = $oplLogoService->downloadTeamLogo($teamEntity->id);
				}
			}
			$saveResult->additionalData['logoDownload'] = $logoDownload;
			$saveResults[] = $saveResult;

			$addedToTournament = $teamInTournamentStageRepo->addTeamToTournamentStage($teamEntity->id, $tournament->id);
			if ($addedToTournament) {
				$addedTeams[] = $saveResult->entity;
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
	 * @return array{matchups: array<RepositorySaveResult>, removedMatchups: array<Matchup>}
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

	/**
	 * @return array<RepositorySaveResult>
	 * @throws \Exception
	 */
	public function calculateStandings(int $tournamentId): array {
		$tournament = $this->tournamentRepo->findById($tournamentId);
		if ($tournament === null) {
			throw new \Exception("Tournament not found", 404);
		}

		if (!$tournament->isStage()) {
			throw new \Exception("Tournament is not a stage with Standings", 400);
		}

		$teamInTournamentStageRepo = new TeamInTournamentStageRepository();
		$teamsInTournamentStage = $teamInTournamentStageRepo->findAllByTournamentStage($tournament);

		$matchupRepo = new MatchupRepository();
		$matchupsInTournamentStage = $matchupRepo->findAllByTournamentStage($tournament);

		foreach ($teamsInTournamentStage as $teamInTournamentStage) {
			$teamInTournamentStage->resetStandings();
			$matchupsToBeConsidered = array_filter($matchupsInTournamentStage, fn($matchup) => (
				$matchup->team1?->team->id === $teamInTournamentStage->team->id || $matchup->team2?->team->id === $teamInTournamentStage->team->id
			));
			foreach ($matchupsToBeConsidered as $matchup) {
				$teamInTournamentStage->addMatchupResultToStanding($matchup);
			}
		}

		usort($teamsInTournamentStage, function($a, $b) {
			if ($a->points === $b->points) {
				return 0;
			}
			return ($a->points > $b->points) ? -1 : 1;
		});

		$standingCounter = 1;
		$prevTeam = null;
		$prevStanding = null;
		foreach ($teamsInTournamentStage as $teamInTournamentStage) {
			if ($teamInTournamentStage->played === 0) {
				continue;
			}

			if ($standingCounter === 1) {
				// Erstes Team als Platz 1 eintragen
				$teamInTournamentStage->standing = 1;
				$prevStanding = 1;
			} elseif ($teamInTournamentStage->points === $prevTeam->points) {
				// Team mit gleicher Punktzahl zu vorigem Team bekommt den gleichen Platz
				$teamInTournamentStage->standing = $prevStanding;
			} else {
				// nächstes Team bekommt nächsten Platz
				$teamInTournamentStage->standing = $standingCounter;
				$prevStanding = $standingCounter;
			}

			$standingCounter++;
			$prevTeam = $teamInTournamentStage;
		}

		$saveResults = [];
		foreach ($teamsInTournamentStage as $teamInTournamentStage) {
			$saveResult = $teamInTournamentStageRepo->save($teamInTournamentStage);
			$saveResults[] = $saveResult;
		}

		return $saveResults;
	}
}