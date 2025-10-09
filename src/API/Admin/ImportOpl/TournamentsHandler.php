<?php

namespace App\API\Admin\ImportOpl;

use App\Core\Utilities\DataParsingHelpers;
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
}