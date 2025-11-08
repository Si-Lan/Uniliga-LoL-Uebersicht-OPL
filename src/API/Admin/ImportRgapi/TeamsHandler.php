<?php

namespace App\API\Admin\ImportRgapi;

use App\API\AbstractHandler;
use App\Service\Updater\TeamUpdater;

class TeamsHandler extends AbstractHandler {
    private TeamUpdater $teamUpdater;
    public function __construct() {
        $this->teamUpdater = new TeamUpdater();
    }

    public function postTeamsRank(int $teamId): void {
        $this->checkRequestMethod('POST');

        try {
            $saveResult = $this->teamUpdater->updateRank($teamId);
        } catch (\Exception $e) {
            $this->sendErrorResponse($e->getCode(), $e->getMessage());
        }

        echo json_encode($saveResult);
    }
}