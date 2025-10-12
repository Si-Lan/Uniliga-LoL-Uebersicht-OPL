<?php

namespace App\API;

use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Entities\Tournament;
use App\Domain\Enums\EventType;
use App\Domain\Repositories\TeamInTournamentRepository;
use App\Domain\Repositories\TournamentRepository;
use JetBrains\PhpStorm\NoReturn;

class TournamentsHandler {
	use DataParsingHelpers;
	private TournamentRepository $tournamentRepo;

	public function __construct() {
		$this->tournamentRepo = new TournamentRepository();
	}

	private function validateAndGetTournament(int $id): Tournament {
		if (!$id) {
			$this->sendErrorResponse(400, 'Missing tournament ID');
		}
		$tournament = $this->tournamentRepo->findById($id);
		if ($tournament === null) {
			$this->sendErrorResponse(404, 'Tournament not found');
		}
		return $tournament;
	}

	#[NoReturn] private function sendErrorResponse(int $statusCode, string $message): void {
		http_response_code($statusCode);
		echo json_encode(['error' => $message]);
		exit;
	}

	public function getTournamentsAll(): void {
		if (isset($_GET["running"]) && $_GET["running"] === "true" ) {
			$tournaments = $this->tournamentRepo->findAllRunningRootTournaments();
		} else {
			$tournaments = $this->tournamentRepo->findAllRootTournaments();
		}
		echo json_encode($tournaments);
	}
	public function getTournaments(int $id): void {
		$tournament = $this->validateAndGetTournament($id);

		echo json_encode($tournament);
	}
	public function getTournamentsLeafes(int $id): void {
		$tournament = $this->validateAndGetTournament($id);

		if ($tournament->eventType === EventType::TOURNAMENT) {
			$tournaments = $this->tournamentRepo->findAllStandingEventsByRootTournament($tournament);
		} else {
			$tournaments = $this->tournamentRepo->findAllStandingEventsByParentTournament($tournament);
		}
		echo json_encode($tournaments);
	}

	public function getTournamentsTeams(int $id): void {
		$tournament = $this->validateAndGetTournament($id);
		if ($tournament->eventType !== EventType::TOURNAMENT) {
			$this->sendErrorResponse(400, 'Event is not a tournament');
		}

		$teamInTournamentRepo = new TeamInTournamentRepository();
		if (!isset($_GET["filterBySubEvent"])) {
			$teams = $teamInTournamentRepo->findAllByRootTournament($tournament);
			echo json_encode($teams);
			exit;
		}

		$subEventId = $this->IntOrNull($_GET["filterBySubEvent"]);
		if ($subEventId === null) {
			$this->sendErrorResponse(400, 'filterBySubEvent is no valid id');
		}

		$subEvent = $this->tournamentRepo->findById($subEventId);
		if ($subEvent === null) {
			$this->sendErrorResponse(404, 'SubEvent not found');
		}
		if ($subEvent->getRootTournament()->id !== $tournament->id || $subEvent->id === $tournament->id) {
			$this->sendErrorResponse(400, 'SubEvent is not a child of tournament');
		}

		if ($subEvent->isStage()) {
			$teams = $teamInTournamentRepo->findAllByStage($subEvent);
		} else {
			$teams = $teamInTournamentRepo->findAllByParentTournament($subEvent);
		}

		echo json_encode($teams);
	}
}