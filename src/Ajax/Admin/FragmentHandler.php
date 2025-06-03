<?php

namespace App\Ajax\Admin;

use App\Domain\Repositories\TournamentRepository;
use App\UI\Components\Admin\TournamentEditForm;

class FragmentHandler {
	public function TournamentEditForm(array $dataGet):void {
		$tournamentRepo = new TournamentRepository();

		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$tournamentData = json_decode(file_get_contents('php://input'), true);
			$tournament = $tournamentRepo->mapToEntity($tournamentData, newEntity: true);
			$newTournament = true;
		} elseif (isset($dataGet['tournamentId'])) {
			$tournament = $tournamentRepo->findById($dataGet['tournamentId']);
			$newTournament = false;
		} else {
			http_response_code(400);
			echo '{"error": "TournamentId or TournamentData missing"}';
			exit();
		}

		$tournamentForm = new TournamentEditForm($tournament,$newTournament);

		echo json_encode(["html"=>$tournamentForm->render()]);
	}
}