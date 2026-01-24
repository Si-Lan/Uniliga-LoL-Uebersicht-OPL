<?php

namespace App\API\Admin;

use App\API\AbstractHandler;
use App\Domain\Repositories\RankedSplitRepository;

class RankedsplitsHandler extends AbstractHandler{
	private RankedSplitRepository $rankedSplitRepo;

	public function __construct() {
		$this->rankedSplitRepo = new RankedSplitRepository();
	}

	public function postRankedSplits(): void {
		$this->checkRequestMethod('POST');
		$splitData = json_decode(file_get_contents('php://input'), true);

		if (json_last_error() !== JSON_ERROR_NONE || !$splitData) {
			$this->sendErrorResponse(400, 'Missing Data or invalid JSON received');
		}

		$neededArrayKeys = ['season','split_start'];
		foreach ($neededArrayKeys as $key) {
			if (!array_key_exists($key, $splitData)) {
				$this->sendErrorResponse(400, "Missing Key '$key' in JSON");
			}
		}
		$splitData['split'] = array_key_exists('split', $splitData) ? $splitData['split'] : 0;
		$splitData['split_end'] = array_key_exists('split_end', $splitData) ? $splitData['split_end'] : null;
		$splitData['split_end'] = $splitData['split_end'] ?: null;

		$RankedSplitEntity = $this->rankedSplitRepo->mapToEntity($splitData);
		if ($this->rankedSplitRepo->rankedSplitExists($RankedSplitEntity->season, $RankedSplitEntity->split)) {
			$this->sendErrorResponse(400, "Split already exists");
		}

		$saveResult = $this->rankedSplitRepo->save($RankedSplitEntity);
		echo json_encode($saveResult);
	}

	public function putRankedSplits(int $season, int $split=0): void {
		$this->checkRequestMethod('PUT');

		$existingRankedSplit = $this->rankedSplitRepo->findBySeasonAndSplit($season, $split);
		if ($existingRankedSplit === null) {
			$this->sendErrorResponse(404, "RankedSplit not found");
		}

		$splitData = json_decode(file_get_contents('php://input'), true);

		if (json_last_error() !== JSON_ERROR_NONE || !$splitData) {
			$this->sendErrorResponse(400, 'Missing Data or invalid JSON received');
		}

		$neededArrayKeys = ['split_start','split_end'];
		foreach ($neededArrayKeys as $key) {
			if (!array_key_exists($key, $splitData)) {
				$this->sendErrorResponse(400, "Missing Key '$key' in JSON");
			}
		}
		$splitData['split_end'] = $splitData['split_end'] ?: null;
		$splitData['season'] = $season;
		$splitData['split'] = $split;

		$rankedSplitEntity = $this->rankedSplitRepo->mapToEntity($splitData);

		$saveResult = $this->rankedSplitRepo->save($rankedSplitEntity);

		echo json_encode($saveResult);
	}

	public function deleteRankedSplits(int $season, int $split=0): void {
		$this->checkRequestMethod('DELETE');

		$existingRankedSplit = $this->rankedSplitRepo->findBySeasonAndSplit($season, $split);
		if ($existingRankedSplit === null) {
			$this->sendErrorResponse(404, "RankedSplit not found");
		}

		$deleted = $this->rankedSplitRepo->delete($existingRankedSplit);
		echo json_encode(['deleted'=>$deleted]);
	}
}