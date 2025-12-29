<?php

namespace App\API\Admin\ImportOpl;

use App\API\AbstractHandler;
use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Enums\EventFormat;
use App\Domain\Enums\EventType;
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

		if ($tournament->dateStart === null && $tournament->getRootTournament()->dateStart !== null) {
			$tournament->dateStart = $tournament->getRootTournament()->dateStart;
		}
		if ($tournament->dateEnd === null && $tournament->getRootTournament()->dateEnd !== null) {
			$tournament->dateEnd = $tournament->getRootTournament()->dateEnd;
		}

		try {
			$format = $oplApi->getFormatByEventId($tournament->id);
		} catch (\Exception $e) {
			$format = null;
		}

		$tournament->format = EventFormat::fromString($format);

		if ($tournament->eventType !== EventType::TOURNAMENT && count($tournamentData['ancestors']) > 0) {
			switch ($tournament->eventType) {
				case EventType::LEAGUE:
				case EventType::WILDCARD:
				case EventType::PLAYOFFS:
					$possibleRootId = $tournamentData['ancestors'][0];
					if ($tournamentRepo->tournamentExists($possibleRootId, EventType::TOURNAMENT)) {
						$possibleParent = $tournamentRepo->findById($possibleRootId);
						$tournament->directParentTournament = $possibleParent;
						$tournament->rootTournament = $possibleParent;
					}
					break;
				case EventType::GROUP:
					foreach ($tournamentData['ancestors'] as $ancestorId) {
						if ($tournamentRepo->tournamentExists($ancestorId, EventType::LEAGUE)) {
							$possibleParent = $tournamentRepo->findById($ancestorId);
							$tournament->directParentTournament = $possibleParent;
							$tournament->rootTournament = $possibleParent->getRootTournament();
						}
					}
					break;
				case EventType::TOURNAMENT:
					break;
			}
		}

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