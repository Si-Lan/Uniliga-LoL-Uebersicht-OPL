<?php

namespace App\Ajax\Admin;

use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Repositories\RankedSplitRepository;
use App\Domain\Repositories\TournamentRepository;
use App\UI\Components\Admin\RankedSplit\RankedSplitRow;
use App\UI\Components\Admin\RelatedTournamentButtonList;
use App\UI\Components\Admin\TournamentEdit\TournamentEditForm;
use App\UI\Components\Admin\TournamentEdit\TournamentEditList;

class FragmentHandler {
	use DataParsingHelpers;
	public function TournamentEditList(array $dataGet):void {
		$openAccordeons = [];
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$openAccordeons = json_decode(file_get_contents('php://input'), true);
			if (json_last_error() !== JSON_ERROR_NONE || !$openAccordeons) {
				http_response_code(400);
				echo json_encode(['error' => 'Missing Data on POST or invalid JSON received']);
				exit;
			}
		}
		$tournamentEditList = new TournamentEditList($openAccordeons);
		echo json_encode(["html"=>$tournamentEditList->render()]);
	}
	public function TournamentEditForm(array $dataGet):void {
		$tournamentRepo = new TournamentRepository();

		$parentIds = [];
		$childrenIds = [];
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$tournamentData = json_decode(file_get_contents('php://input'), true);
			$tournament = $tournamentRepo->mapToEntity($tournamentData['entityData'], newEntity: true);
			$parentIds = $tournamentData['relatedTournaments']['parents'] ?? [];
			$childrenIds = $tournamentData['relatedTournaments']['children'] ?? [];
			$newTournament = true;
		} elseif (isset($dataGet['tournamentId'])) {
			$tournament = $tournamentRepo->findById($dataGet['tournamentId']);
			$newTournament = false;
		} else {
			http_response_code(400);
			echo '{"error": "TournamentId or TournamentData missing"}';
			exit();
		}

		$tournamentForm = new TournamentEditForm($tournament,$newTournament, $parentIds, $childrenIds);

		echo json_encode(["html"=>$tournamentForm->render()]);
	}

	public function RelatedTournamentList(array $dataGet):void {
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$tournamentData = json_decode(file_get_contents('php://input'), true);
			if (json_last_error() !== JSON_ERROR_NONE || !$tournamentData) {
				http_response_code(400);
				echo json_encode(['error' => 'Missing Data or invalid JSON received']);
				exit;
			}
		} else {
			http_response_code(400);
			echo '{"error": "TournamentData missing"}';
			exit();
		}

		$tournamentButtonList = new RelatedTournamentButtonList($tournamentData);

		echo json_encode(["html"=>$tournamentButtonList->render()]);
	}

	public function RankedSplitRows(): void {
		$rankedSplitRepo = new RankedSplitRepository();
		$rankedSplits = $rankedSplitRepo->findAll();
		$rankedSplitRows = "";
		foreach ($rankedSplits as $rankedSplit) {
			$rankedSplitRows .= new RankedSplitRow($rankedSplit);
		}
		echo json_encode(["html"=>$rankedSplitRows]);
	}

	public function RankedSplitRow(array $dataGet): void {
		$season = $this->intOrNull($dataGet['season']??null);
		$split = $this->intOrNull($dataGet['split']??null);
		if ($season === null || $split === null) {
			$rankedSplitRow = new RankedSplitRow();
			echo json_encode(["html" => $rankedSplitRow->render()]);
			return;
		}

		$rankedSplitRepo = new RankedSplitRepository();
		$rankedSplit = $rankedSplitRepo->findBySeasonAndSplit($season, $split);
		$rankedSplitRow = new RankedSplitRow($rankedSplit);
		echo json_encode(["html" => $rankedSplitRow->render()]);
	}
}