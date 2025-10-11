<?php

namespace App\API;

use App\Domain\Entities\Tournament;
use App\Domain\Enums\EventType;
use App\Domain\Repositories\TournamentRepository;
use JetBrains\PhpStorm\NoReturn;

class TournamentsHandler {
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
}