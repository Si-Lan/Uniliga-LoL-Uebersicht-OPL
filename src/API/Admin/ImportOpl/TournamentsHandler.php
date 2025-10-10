<?php

namespace App\API\Admin\ImportOpl;

use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Entities\Team;
use App\Domain\Repositories\TeamInTournamentStageRepository;
use App\Domain\Repositories\TeamRepository;
use App\Domain\Repositories\TournamentRepository;
use App\Service\OplApiService;
use App\Service\OplLogoService;

class TournamentsHandler {
	use DataParsingHelpers;

	/**
	 * Returns entityData and relatedTournaments without saving to Database
	 */
	public function getTournaments(int $id): void {
		if (!$id) {
			http_response_code(400);
			echo json_encode(['error' => 'Missing tournament ID']);
			exit;
		}

		$oplApi = new OplApiService();
		try {
			$tournamentData = $oplApi->fetchFromEndpoint("tournament/$id");
		} catch (\Exception $e) {
			http_response_code(500);
			echo json_encode(['error' => 'Failed to fetch data from OPL API: ' . $e->getMessage()]);
			exit;
		}

		$tournamentRepo = new TournamentRepository();

		$tournament = $tournamentRepo->createFromOplData($tournamentData);
		sort($tournamentData['leafes']);
		sort($tournamentData['ancestors']);

		$relatedEvents  = ["children"=>$tournamentData['leafes'], "parents"=>$tournamentData['ancestors']];

		echo json_encode(["entityData" => $tournamentRepo->mapEntityToData($tournament), "relatedTournaments" => $relatedEvents]);
	}

	/**
	 * Takes Tournaments in correct entityData and saves to Database
	 */
	public function postTournaments(): void {
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			http_response_code(400);
			echo json_encode(['error' => 'Invalid request method']);
			exit;
		}
		$tournamentData = json_decode(file_get_contents('php://input'), true);

		if (json_last_error() !== JSON_ERROR_NONE || !$tournamentData) {
			http_response_code(400);
			echo json_encode(['error' => 'Missing Data or invalid JSON received']);
			exit;
		}

		$tournamentRepo = new TournamentRepository();
		$tournament = $tournamentRepo->mapToEntity($tournamentData, newEntity: true);

		$saveResult = $tournamentRepo->save($tournament);
		$saveResult["result"] = $saveResult["result"]->name;
		echo json_encode($saveResult);
	}

	public function postTournamentsLogos($id): void {
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			http_response_code(400);
			echo json_encode(['error' => 'Invalid request method']);
			exit;
		}

		$oplLogoService = new OplLogoService();

		$logoDownload = $oplLogoService->downloadTournamentLogo($id);

		echo json_encode($logoDownload);
	}

	public function postTournamentsTeams($id): void {
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			http_response_code(400);
			echo json_encode(['error' => 'Invalid request method']);
			exit;
		}

		$tournamentRepo = new TournamentRepository();
		$tournament = $tournamentRepo->findById($id);
		if ($tournament === null) {
			http_response_code(400);
			echo json_encode(['error' => 'Tournament not found']);
			exit;
		}
		if (!$tournament->isEventWithStanding()) {
			http_response_code(400);
			echo json_encode(['error' => 'Tournament is not a Group with teams']);
			exit;
		}

		$oplApi = new OplApiService();
		try {
			$tournamentData = $oplApi->fetchFromEndpoint("tournament/$id/team_registrations");
		} catch (\Exception $e) {
			http_response_code(500);
			echo json_encode(['error' => 'Failed to fetch data from OPL API: ' . $e->getMessage()]);
			exit;
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
			$saveResult["result"] = $saveResult["result"]->name;
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

		echo json_encode(['teams'=>$saveResults,'removedTeams'=>$removedTeams,'addedTeams'=>$addedTeams]);
	}
}