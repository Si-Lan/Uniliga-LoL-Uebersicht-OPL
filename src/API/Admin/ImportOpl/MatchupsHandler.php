<?php

namespace App\API\Admin\ImportOpl;

use App\API\AbstractHandler;
use App\Service\Updater\MatchupUpdater;

class MatchupsHandler extends AbstractHandler {
	private MatchupUpdater $matchupUpdater;
	public function __construct() {
		$this->matchupUpdater = new MatchupUpdater();
	}

	public function postMatchupsResults(int $matchupId): void {
		$this->checkRequestMethod('POST');

		try {
			$saveResult = $this->matchupUpdater->updateMatchupResults($matchupId);
		} catch (\Exception $e) {
			$this->sendErrorResponse($e->getCode(), $e->getMessage());
		}

		echo json_encode($saveResult);
	}
}