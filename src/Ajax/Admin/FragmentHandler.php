<?php

namespace App\Ajax\Admin;

use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Repositories\RankedSplitRepository;
use App\Domain\Repositories\TournamentRepository;
use App\UI\Components\Admin\PatchData\AddPatchesView;
use App\UI\Components\Admin\PatchData\PatchDataRows;
use App\UI\Components\Admin\RankedSplit\RankedSplitRow;
use App\UI\Components\Admin\RelatedTournamentButtonList;
use App\UI\Components\Admin\TournamentEdit\TournamentEditForm;
use App\UI\Components\Admin\TournamentEdit\TournamentEditList;
use App\UI\Components\Admin\JobItem;
use App\Service\LogViewer;
use App\UI\Page\AssetManager;

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
			$tournament = $tournamentRepo->buildTournament($tournamentData['entityData'], newEntity: true);
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

		echo json_encode(["html"=>$tournamentForm->render(), "js"=>AssetManager::getJsFiles(), "css"=>AssetManager::getCssFiles()]);
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

	public function addPatchesRows(array $dataGet): void {
		$type = $this->stringOrNull($dataGet['type'] ?? "new");
		$patchView = new AddPatchesView(type: $type, onlyRows: true);
		echo json_encode(["html" => $patchView->render()]);
	}
	public function PatchesList(array $dataGet): void {
		$patchesList = new PatchDataRows();
		echo json_encode(["html" => $patchesList->render()]);
	}

	public function JobItem(array $dataGet): void {
		$jobId = $this->intOrNull($dataGet['jobId'] ?? null);
		if ($jobId === null) {
			http_response_code(400);
			echo json_encode(['error' => 'Missing jobId parameter']);
			return;
		}

		$logViewer = new LogViewer();
		$jobDetails = $logViewer->getJobDetails($jobId);

		if ($jobDetails === null) {
			http_response_code(404);
			echo json_encode(['error' => 'Job not found']);
			return;
		}

		$jobItem = new JobItem($jobDetails);
		echo json_encode(["html" => $jobItem->render()]);
	}
}