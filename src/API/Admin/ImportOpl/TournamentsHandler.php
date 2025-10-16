<?php

namespace App\API\Admin\ImportOpl;

use App\API\AbstractHandler;
use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Repositories\TournamentRepository;
use App\Service\OplApiService;
use App\Service\OplLogoService;
use App\Service\Updater\TournamentUpdater;

class TournamentsHandler extends AbstractHandler{
	use DataParsingHelpers;

	private TournamentUpdater $tournamentUpdater;
	public function __construct() {
		$this->tournamentUpdater = new TournamentUpdater();
	}

	/**
	 * Returns entityData and relatedTournaments without saving to Database
	 */
	public function getTournaments(int $id): void {
		if (!$id) $this->sendErrorResponse(400, "Missing tournament ID");

		$oplApi = new OplApiService();
		try {
			$tournamentData = $oplApi->fetchFromEndpoint("tournament/$id");
		} catch (\Exception $e) {
			$this->sendErrorResponse(500, 'Failed to fetch data from OPL API: ' . $e->getMessage());
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
		$this->checkRequestMethod('POST');
		$tournamentData = json_decode(file_get_contents('php://input'), true);

		if (json_last_error() !== JSON_ERROR_NONE || !$tournamentData) {
			$this->sendErrorResponse(400, 'Missing Data or invalid JSON received');
		}

		$tournamentRepo = new TournamentRepository();
		$tournament = $tournamentRepo->mapToEntity($tournamentData, newEntity: true);

		$saveResult = $tournamentRepo->save($tournament);
		echo json_encode($saveResult);
	}

	public function postTournamentsLogos($id): void {
		$this->checkRequestMethod('POST');

		$oplLogoService = new OplLogoService();

		$logoDownload = $oplLogoService->downloadTournamentLogo($id);

		echo json_encode($logoDownload);
	}

	public function postTournamentsTeams($id): void {
		$this->checkRequestMethod('POST');

		try {
			$saveResult = $this->tournamentUpdater->updateTeams($id);
		} catch (\Exception $e) {
			$this->sendErrorResponse($e->getCode(), $e->getMessage());
		}

		echo json_encode($saveResult);
	}

	public function postTournamentsMatchups($tournamentId): void {
		$this->checkRequestMethod('POST');

		try {
			$saveResult = $this->tournamentUpdater->updateMatchups($tournamentId);
		} catch (\Exception $e) {
			$this->sendErrorResponse($e->getCode(), $e->getMessage());
		}

		echo json_encode($saveResult);
	}

	public function postTournamentsStandings($tournamentId): void {
		$this->checkRequestMethod('POST');

		try {
			$standingResult = $this->tournamentUpdater->calculateStandings($tournamentId);
		} catch (\Exception $e) {
			$this->sendErrorResponse($e->getCode(), $e->getMessage());
		}

		echo json_encode($standingResult);
	}
}