<?php

namespace App\Ajax\Admin;

use App\Ajax\AbstractFragmentHandler;
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

class FragmentHandler extends AbstractFragmentHandler{
	use DataParsingHelpers;
	public function TournamentEditList(array $dataGet):void {
		$openAccordeons = [];
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$openAccordeons = json_decode(file_get_contents('php://input'), true);
			if (json_last_error() !== JSON_ERROR_NONE || !$openAccordeons) {
				$this->sendJsonError('Missing Data on POST or invalid JSON received', 400);
			}
		}
		$tournamentEditList = new TournamentEditList($openAccordeons);
		$this->sendJsonFragment($tournamentEditList->render());
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
			$this->sendJsonError('TournamentId or TournamentData missing', 400);
		}

		$tournamentForm = new TournamentEditForm($tournament,$newTournament, $parentIds, $childrenIds);

		$this->sendJsonFragment($tournamentForm->render());
	}

	public function RelatedTournamentList(array $dataGet):void {
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$tournamentData = json_decode(file_get_contents('php://input'), true);
			if (json_last_error() !== JSON_ERROR_NONE || !$tournamentData) {
				$this->sendJsonError('Missing Data or invalid JSON received', 400);
			}
		} else {
			$this->sendJsonError('TournamentData missing', 400);
		}

		$tournamentButtonList = new RelatedTournamentButtonList($tournamentData);

		$this->sendJsonFragment($tournamentButtonList->render());
	}

	public function RankedSplitRows(): void {
		$rankedSplitRepo = new RankedSplitRepository();
		$rankedSplits = $rankedSplitRepo->findAll();
		$rankedSplitRows = "";
		foreach ($rankedSplits as $rankedSplit) {
			$rankedSplitRows .= new RankedSplitRow($rankedSplit);
		}
		$this->sendJsonFragment($rankedSplitRows);
	}

	public function RankedSplitRow(array $dataGet): void {
		$season = $this->intOrNull($dataGet['season']??null);
		$split = $this->intOrNull($dataGet['split']??null);
		if ($season === null || $split === null) {
			$rankedSplitRow = new RankedSplitRow();
			$this->sendJsonFragment($rankedSplitRow->render());
			return;
		}

		$rankedSplitRepo = new RankedSplitRepository();
		$rankedSplit = $rankedSplitRepo->findBySeasonAndSplit($season, $split);
		$rankedSplitRow = new RankedSplitRow($rankedSplit);
		$this->sendJsonFragment($rankedSplitRow->render());
	}

	public function addPatchesRows(array $dataGet): void {
		$type = $this->stringOrNull($dataGet['type'] ?? "new");
		$patchView = new AddPatchesView(type: $type, onlyRows: true);
		$this->sendJsonFragment($patchView->render());
	}
	public function PatchesList(array $dataGet): void {
		$patchesList = new PatchDataRows();
		$this->sendJsonFragment($patchesList->render());
	}

	public function JobItem(array $dataGet): void {
		$jobId = $this->intOrNull($dataGet['jobId'] ?? null);
		if ($jobId === null) {
			$this->sendJsonError('Missing jobId parameter', 400);
		}

		$logViewer = new LogViewer();
		$jobDetails = $logViewer->getJobDetails($jobId);

		if ($jobDetails === null) {
			$this->sendJsonError('Job not found', 404);
		}

		$jobItem = new JobItem($jobDetails);
		$this->sendJsonFragment($jobItem->render());
	}
}