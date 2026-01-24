<?php

namespace App\API;

use App\Domain\Repositories\TeamRepository;

class TeamsHandler extends AbstractHandler {
	private TeamRepository $teamRepo;

	public function __construct() {
		$this->teamRepo = new TeamRepository();
	}

	public function getTeamsAll(): void {
		$teams = $this->teamRepo->findAll();
		echo json_encode($teams);
	}
}