<?php

namespace App\API\Admin\ImportRgapi;

use App\API\AbstractHandler;
use App\Domain\Enums\Jobs\UpdateJobAction;
use App\Domain\Enums\Jobs\UpdateJobContextType;
use App\Domain\Enums\Jobs\UpdateJobType;
use App\Domain\Repositories\TournamentRepository;
use App\Domain\Repositories\UpdateJobRepository;

class TournamentsHandler extends AbstractHandler {
	private TournamentRepository $tournamentRepo;
	private UpdateJobRepository $updateJobRepo;
	public function __construct() {
		$this->tournamentRepo = new TournamentRepository();
		$this->updateJobRepo = new UpdateJobRepository();
	}


	public function postTournamentsPlayersPuuids(int $tournamentId): void {
		$this->checkRequestMethod('POST');
		$withoutPuuidOnly = isset($_GET["withoutPuuid"]) && $_GET["withoutPuuid"] === "true";

		if ($this->tournamentRepo->tournamentExists($tournamentId) === false) {
			$this->sendErrorResponse(404, "Tournament not found");
		}

		$job = $this->updateJobRepo->createJob(UpdateJobType::ADMIN, UpdateJobAction::UPDATE_PUUIDS, UpdateJobContextType::TOURNAMENT, $tournamentId);

		$commandOptions = ["-j $job->id"];
		if ($withoutPuuidOnly) $commandOptions[] = "--without-puuid";

		$optionString = implode(" ", $commandOptions);
		exec("php ".BASE_PATH."/bin/admin_updates/update_puuids.php $optionString > /dev/null 2>&1 &");

		echo json_encode(['job_id'=>$job->id]);
	}

    public function postTournamentsPlayersRiotids(int $tournamentId): void {
        $this->checkRequestMethod('POST');
        if ($this->tournamentRepo->tournamentExists($tournamentId) === false) {
            $this->sendErrorResponse(404, "Tournament not found");
        }

        $job = $this->updateJobRepo->createJob(UpdateJobType::ADMIN, UpdateJobAction::UPDATE_RIOTIDS_PUUIDS, UpdateJobContextType::TOURNAMENT, $tournamentId);

        $optionsString = "-j $job->id";
        exec("php ".BASE_PATH."/bin/admin_updates/update_riotids_by_puuid.php $optionsString > /dev/null 2>&1 &");

        echo json_encode(['job_id'=>$job->id]);
    }

    public function postTournamentsPlayersRanks(int $tournamentId): void {
        $this->checkRequestMethod('POST');
        if ($this->tournamentRepo->tournamentExists($tournamentId) === false) {
            $this->sendErrorResponse(404, "Tournament not found");
        }

        $job = $this->updateJobRepo->createJob(UpdateJobType::ADMIN, UpdateJobAction::UPDATE_PLAYER_RANKS, UpdateJobContextType::TOURNAMENT, $tournamentId);

        $optionsString = "-j $job->id";
        exec("php ".BASE_PATH."/bin/admin_updates/update_player_ranks.php $optionsString > /dev/null 2>&1 &");

        echo json_encode(['job_id'=>$job->id]);
    }

    public function postTournamentsTeamsRanks(int $tournamentId): void {
        $this->checkRequestMethod('POST');
        if ($this->tournamentRepo->tournamentExists($tournamentId) === false) {
            $this->sendErrorResponse(404, "Tournament not found");
        }

        $job = $this->updateJobRepo->createJob(UpdateJobType::ADMIN, UpdateJobAction::UPDATE_TEAM_RANKS, UpdateJobContextType::TOURNAMENT, $tournamentId);

        $optionsString = "-j $job->id";
        exec("php ".BASE_PATH."/bin/admin_updates/update_team_ranks.php $optionsString > /dev/null 2>&1 &");

        echo json_encode(['job_id'=>$job->id]);
    }

    public function postTournamentsGamesData(int $tournamentId): void {
        $this->checkRequestMethod('POST');
        if ($this->tournamentRepo->tournamentExists($tournamentId) === false) {
            $this->sendErrorResponse(404, "Tournament not found");
        }

        $job = $this->updateJobRepo->createJob(UpdateJobType::ADMIN, UpdateJobAction::UPDATE_GAMEDATA, UpdateJobContextType::TOURNAMENT, $tournamentId);

        $optionsString = "-j $job->id";
        exec("php ".BASE_PATH."/bin/admin_updates/update_gamedata.php $optionsString > /dev/null 2>&1 &");

        echo json_encode(['job_id'=>$job->id]);
    }

    public function postTournamentsPlayersStats(int $tournamentId): void {
        $this->checkRequestMethod('POST');
        if ($this->tournamentRepo->tournamentExists($tournamentId) === false) {
            $this->sendErrorResponse(404, "Tournament not found");
        }

        $job = $this->updateJobRepo->createJob(UpdateJobType::ADMIN, UpdateJobAction::UPDATE_PLAYER_STATS, UpdateJobContextType::TOURNAMENT, $tournamentId);

        $optionsString = "-j $job->id";
        exec("php ".BASE_PATH."/bin/admin_updates/update_player_stats.php $optionsString > /dev/null 2>&1 &");

        echo json_encode(['job_id'=>$job->id]);
    }

    public function postTournamentsTeamsStats(int $tournamentId): void {
        $this->checkRequestMethod('POST');
        if ($this->tournamentRepo->tournamentExists($tournamentId) === false) {
            $this->sendErrorResponse(404, "Tournament not found");
        }

        $job = $this->updateJobRepo->createJob(UpdateJobType::ADMIN, UpdateJobAction::UPDATE_TEAM_STATS, UpdateJobContextType::TOURNAMENT, $tournamentId);

        $optionsString = "-j $job->id";
        exec("php ".BASE_PATH."/bin/admin_updates/update_team_stats.php $optionsString > /dev/null 2>&1 &");

        echo json_encode(['job_id'=>$job->id]);
    }
}