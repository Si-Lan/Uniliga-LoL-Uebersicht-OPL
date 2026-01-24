<?php

namespace App\API\Admin\ImportOpl;

use App\API\AbstractHandler;
use App\Service\Updater\PlayerUpdater;

class PlayersHandler extends AbstractHandler {
	private PlayerUpdater $playerUpdater;
	public function __construct() {
		$this->playerUpdater = new PlayerUpdater();
	}

	public function postPlayersAccount(int $playerId): void {
		$this->checkRequestMethod('POST');

		try {
			$saveResult = $this->playerUpdater->updatePlayerAccount($playerId);
		} catch (\Exception $e) {
			$this->sendErrorResponse($e->getCode(), $e->getMessage());
		}

		echo json_encode($saveResult);
	}
}