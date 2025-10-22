<?php

namespace App\API\Admin\ImportOpl;

use App\API\AbstractHandler;
use App\Core\Utilities\DataParsingHelpers;
use App\Service\Updater\PlayerUpdater;
use App\Service\Updater\TeamUpdater;

class TeamsHandler extends AbstractHandler {
	use DataParsingHelpers;

	private TeamUpdater $teamUpdater;
	public function __construct() {
		$this->teamUpdater = new TeamUpdater();
	}

	public function postTeamsPlayers(int $teamId): void {
		$this->checkRequestMethod('POST');

		try {
			$saveResult = $this->teamUpdater->updatePlayers($teamId);
		} catch (\Exception $e) {
			$this->sendErrorResponse($e->getCode(), $e->getMessage());
		}

		echo json_encode($saveResult);
	}

	public function postTeamsPlayersAccounts(int $teamId): void {
		$this->checkRequestMethod('POST');

		$playerUpdater = new PlayerUpdater();

		try {
			$saveResult = $playerUpdater->updatePlayerAccountsForTeam($teamId);
		} catch (\Exception $e) {
			$this->sendErrorResponse($e->getCode(), $e->getMessage());
		}

		echo json_encode($saveResult);
	}

	public function postTeamsUpdate(int $teamId): void {
		$this->checkRequestMethod('POST');

		try {
			$saveResult = $this->teamUpdater->updateTeam($teamId);
		} catch (\Exception $e) {
			$this->sendErrorResponse($e->getCode(), $e->getMessage());
		}

		echo json_encode($saveResult);
	}
}