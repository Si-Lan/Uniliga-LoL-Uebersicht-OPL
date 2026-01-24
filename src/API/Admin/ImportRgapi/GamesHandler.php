<?php

namespace App\API\Admin\ImportRgapi;

use App\API\AbstractHandler;
use App\Service\Updater\GameUpdater;

class GamesHandler extends AbstractHandler {
    private GameUpdater $gameUpdater;
    public function __construct() {
        $this->gameUpdater = new GameUpdater();
    }

    public function postGamesData(): void {
        $this->checkRequestMethod('POST');
        $body = $this->parseRequestData();

        $gameId = $body['id']??null;
        if (!is_string($gameId)) {
            $this->sendErrorResponse(400, "Missing game id");
        }

        try {
            $saveResult = $this->gameUpdater->updateGameData($gameId);
        } catch (\Exception $e) {
            $this->sendErrorResponse($e->getCode(), $e->getMessage());
        }

        echo json_encode($saveResult);
    }
}