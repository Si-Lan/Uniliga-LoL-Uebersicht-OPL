<?php

namespace App\API;

use App\Domain\Enums\Jobs\UpdateJobAction;
use App\Domain\Enums\Jobs\UpdateJobContextType;
use App\Domain\Enums\Jobs\UpdateJobStatus;
use App\Domain\Enums\Jobs\UpdateJobType;
use App\Domain\Repositories\TeamInTournamentRepository;
use App\Domain\Repositories\TournamentRepository;
use App\Domain\Repositories\UpdateJobRepository;

class JobsHandler extends AbstractHandler {
	private UpdateJobRepository $jobRepo;
	public function __construct() {
		$this->jobRepo = new UpdateJobRepository();
	}

	public function getJobs(int $jobId): void {
		$job = $this->jobRepo->findById($jobId);
		if ($job === null) {
			$this->sendErrorResponse(404, 'Job not found');
		}
		echo json_encode($job?->getApiOutput());
	}

    public function getJobsUserGroupRunning(int $groupId): void {
        $runningJob = $this->jobRepo->findLatest(
            UpdateJobType::USER,
            UpdateJobAction::UPDATE_GROUP,
            UpdateJobStatus::RUNNING,
            UpdateJobContextType::GROUP,
            $groupId
        );
        echo json_encode($runningJob?->getApiOutput());
    }

    public function postJobsUserGroup(int $groupId): void {
        $this->checkRequestMethod('POST');

        $tournamentRepo = new TournamentRepository();
        $event = $tournamentRepo->findById($groupId);
        if ($event === null) {
            $this->sendErrorResponse(404, "Event not found");
        }
        if (!$event->isStage()) {
            $this->sendErrorResponse(400, "Event is not a stage event");
        }

        $runningJob = $this->jobRepo->findLatest(
            UpdateJobType::USER,
            UpdateJobAction::UPDATE_GROUP,
            UpdateJobStatus::RUNNING,
            UpdateJobContextType::GROUP,
            $event->id
        );

        if ($runningJob !== null) {
            echo json_encode($runningJob->getApiOutput());
            exit;
        }

        $lastJob = $this->jobRepo->findLatest(
            UpdateJobType::USER,
            UpdateJobAction::UPDATE_GROUP,
            UpdateJobStatus::SUCCESS,
            UpdateJobContextType::GROUP,
            $event->id
        );

        if ($lastJob !== null) {
			$latestTime = $lastJob->finishedAt ?? $lastJob->updatedAt;
			$currentTime = new \DateTimeImmutable();
			$diff = $currentTime->diff($latestTime, true);
			if ($diff->i < 10) {
				http_response_code(429);
				echo json_encode($lastJob->getApiOutput());
				exit;
			}
		}

        $job = $this->jobRepo->createJob(
            UpdateJobType::USER,
            UpdateJobAction::UPDATE_GROUP,
            UpdateJobContextType::GROUP,
            $groupId
		);

		$optionsString = "-j $job->id";
		exec("php ".BASE_PATH."/bin/user_updates/user_update_group.php $optionsString > /dev/null 2>&1 &");

        echo json_encode($job->getApiOutput());
    }

	public function getJobsUserTeamTournamentRunning(int $teamId, int $tournamentId): void {
		$runningJob = $this->jobRepo->findLatest(
			UpdateJobType::USER,
			UpdateJobAction::UPDATE_TEAM,
			UpdateJobStatus::RUNNING,
			UpdateJobContextType::TEAM,
			contextId: $teamId,
			tournamentId: $tournamentId
		);
		echo json_encode($runningJob?->getApiOutput());
	}

	public function postJobsUserTeamTournament(int $teamId, int $tournamentId): void {
		$this->checkRequestMethod('POST');

		$teamInTournamentRepo = new TeamInTournamentRepository();
		$teamInTournament = $teamInTournamentRepo->findByTeamIdAndTournamentId($teamId, $tournamentId);
		if ($teamInTournament === null) {
			$this->sendErrorResponse(404, "Team not found in tournament");
		}

		$runningJob = $this->jobRepo->findLatest(
			UpdateJobType::USER,
			UpdateJobAction::UPDATE_TEAM,
			UpdateJobStatus::RUNNING,
			UpdateJobContextType::TEAM,
			contextId: $teamInTournament->team->id,
			tournamentId: $teamInTournament->tournament->id
		);

		if ($runningJob !== null) {
			echo json_encode($runningJob->getApiOutput());
			exit;
		}

		$latestJob = $this->jobRepo->findLatest(
			UpdateJobType::USER,
			UpdateJobAction::UPDATE_TEAM,
			UpdateJobStatus::SUCCESS,
			UpdateJobContextType::TEAM,
			contextId: $teamInTournament->team->id,
			tournamentId: $teamInTournament->tournament->id
		);

		if ($latestJob !== null) {
			$latestTime = $latestJob->finishedAt ?? $latestJob->updatedAt;
			$currentTime = new \DateTimeImmutable();
			$diff = $currentTime->diff($latestTime, true);
			if ($diff->i < 10) {
				http_response_code(429);
				echo json_encode($latestJob->getApiOutput());
				exit;
			}
		}

		$job = $this->jobRepo->createJob(
			UpdateJobType::USER,
			UpdateJobAction::UPDATE_TEAM,
			UpdateJobContextType::TEAM,
			contextId: $teamInTournament->team->id,
			tournamentId: $teamInTournament->tournament->id
		);

		$optionsString = "-j $job->id";
		exec("php ".BASE_PATH."/bin/user_updates/user_update_team.php $optionsString > ".BASE_PATH."/logs/test.log 2>&1 &");

		echo json_encode($job->getApiOutput());
	}
}