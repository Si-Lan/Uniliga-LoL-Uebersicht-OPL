<?php

namespace App\API\Admin\ImportRgapi;

use App\API\AbstractHandler;
use App\Service\Updater\PlayerUpdater;

class PlayersHandler extends AbstractHandler {
	private PlayerUpdater $playerUpdater;
	public function __construct() {
		$this->playerUpdater = new PlayerUpdater();
	}

	public function postPlayersPuuid(int $playerId): void {
		$this->checkRequestMethod('POST');

		try {
			$saveResult = $this->playerUpdater->updatePuuidByRiotId($playerId);
		} catch (\Exception $e) {
			$this->sendErrorResponse($e->getCode(), $e->getMessage());
		}

		echo json_encode($saveResult);
	}

    public function postPlayersRiotid(int $playerId): void {
        $this->checkRequestMethod('POST');

        try {
            $saveResult = $this->playerUpdater->updateRiotIdByPuuid($playerId);
        } catch (\Exception $e) {
            $this->sendErrorResponse($e->getCode(), $e->getMessage());
        }

        echo json_encode($saveResult);
    }
}