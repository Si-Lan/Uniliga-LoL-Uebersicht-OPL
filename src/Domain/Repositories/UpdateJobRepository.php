<?php

namespace App\Domain\Repositories;

use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Entities\Patch;
use App\Domain\Entities\UpdateJob;
use App\Domain\Enums\Jobs\UpdateJobAction;
use App\Domain\Enums\Jobs\UpdateJobContextType;
use App\Domain\Enums\Jobs\UpdateJobStatus;
use App\Domain\Enums\Jobs\UpdateJobType;
use App\Domain\Enums\SaveResult;
use App\Domain\ValueObjects\RepositorySaveResult;

class UpdateJobRepository extends AbstractRepository {
	use DataParsingHelpers;
	private TournamentRepository $tournamentRepo;
	private TeamRepository $teamRepo;
	private MatchupRepository $matchupRepo;
	private PatchRepository $patchRepo;
	protected static array $ALL_DATA_KEYS = ["id", "type", "action", "status", "progress", "context_type", "context_id", "tournament_id", "started_at", "finished_at", "message", "result_message", "created_at", "updated_at", "pid"];
	protected static array $REQUIRED_DATA_KEYS = ["id", "type", "action"];

	public function __construct() {
		parent::__construct();
		$this->tournamentRepo = new TournamentRepository();
		$this->teamRepo = new TeamRepository();
		$this->matchupRepo = new MatchupRepository();
		$this->patchRepo = new PatchRepository();
	}
	public function mapToEntity(array $data): UpdateJob {
		$data = $this->normalizeData($data);

		$context = null;
		$contextId = $this->intOrNull($data['context_id']);
		$contextName = $this->stringOrNull($data['context_name']);
		if ($contextId !== null) {
			switch ($data['context_type']) {
				case UpdateJobContextType::TOURNAMENT->value:
				case UpdateJobContextType::GROUP->value:
					$context = $this->tournamentRepo->findById($contextId);
					break;
				case UpdateJobContextType::TEAM->value:
					$context = $this->teamRepo->findById($contextId);
					break;
				case UpdateJobContextType::MATCHUP->value:
					$context = $this->matchupRepo->findById($contextId);
					break;
			}
		} elseif ($contextName !== null) {
			if ($data['context_type'] === UpdateJobContextType::PATCH->value) {
				$context = $this->patchRepo->findByPatchNumber($contextName);
			}
		}
		$tournament = null;
		$tournamentId = $this->intOrNull($data['tournament_id']);
		if ($tournamentId !== null) {
			$tournament = $this->tournamentRepo->findById($tournamentId);
		}

		return new UpdateJob(
			id: (int) $data['id'],
			type: UpdateJobType::tryFrom($data['type']),
			action: UpdateJobAction::tryFrom($data['action']),
			status: UpdateJobStatus::tryFrom($data['status']??''),
			progress: floatval($data['progress']),
			contextType: UpdateJobContextType::tryFrom($data['context_type']??''),
			context: $context,
			tournament: $tournament,
			startedAt: $this->DateTimeImmutableOrNull($data['started_at']),
			finishedAt: $this->DateTimeImmutableOrNull($data['finished_at']),
			message: $this->stringOrNull($data['message']),
			resultMessage: $this->stringOrNull($data['result_message']),
			createdAt: $this->DateTimeImmutableOrNull($data['created_at']),
			updatedAt: $this->DateTimeImmutableOrNull($data['updated_at']),
			pid: $this->intOrNull($data['pid'])??null
		);
	}
	public function mapEntityToData(UpdateJob $job): array {
		$data = [];
		$data['id'] = $job->id;
		$data['type'] = $job->type->value;
		$data['action'] = $job->action->value;
		$data['context_type'] = $job->contextType?->value;
		if ($job->context !== null && property_exists($job->context,'id')) {
			$data['context_id'] = $job->context->id;
		} else {
			$data['context_id'] = null;
		}
		if ($job->context instanceof Patch) {
			$data['context_name'] = $job->context->patchNumber;
		} else {
			$data['context_name'] = null;
		}
		$data['tournament_id'] = $job->tournament?->id;
		$data['started_at'] = $job->startedAt?->format('Y-m-d H:i:s');
		$data['finished_at'] = $job->finishedAt?->format('Y-m-d H:i:s');
		$data['status'] = $job->status->value;
		$data['progress'] = $job->progress;
		$data['message'] = $job->message;
		$data['result_message'] = $job->resultMessage;
		$data['pid'] = $job->pid;
		return $data;
	}

	public function findById(int $id): ?UpdateJob {
		$query = 'SELECT * FROM update_jobs WHERE id = ?';
		$result = $this->dbcn->execute_query($query, [$id]);
		$data = $result->fetch_assoc();
		return $data ? $this->mapToEntity($data) : null;

	}

	public function findByIds(array $ids): array {
		if (count($ids) === 0) return [];
		$placeholders = implode(',', array_fill(0, count($ids), '?'));
		$query = "SELECT * FROM update_jobs WHERE id IN ($placeholders)";
		$result = $this->dbcn->execute_query($query, $ids);
		$data = $result->fetch_all(MYSQLI_ASSOC);
		$jobs = [];
		foreach ($data as $jobData) {
			$jobs[] = $this->mapToEntity($jobData);
		}
		return $jobs;
	}

	/**
	 * @param UpdateJobType|null $type
	 * @param UpdateJobAction|null $action
	 * @param UpdateJobStatus|null $status
	 * @param UpdateJobContextType|null $contextType
	 * @param int|null $contextId
	 * @param string|null $contextName
	 * @param int|null $tournamentId
	 * @return array<UpdateJob>
	 */
	public function findAll(?UpdateJobType $type = null, ?UpdateJobAction $action = null, ?UpdateJobStatus $status = null, ?UpdateJobContextType $contextType = null, ?int $contextId = null, ?string $contextName = null, ?int $tournamentId = null): array {
		/** @noinspection SqlConstantExpression */
		$query = 'SELECT * FROM update_jobs WHERE 1 = 1';
		$params = [];
		if ($type !== null) {
			$query .= ' AND type = ?';
			$params[] = $type->value;
		}
		if ($action !== null) {
			$query .= ' AND action = ?';
			$params[] = $action->value;
		}
		if ($status !== null) {
			$query .= ' AND status = ?';
			$params[] = $status->value;
		}
		if ($contextType !== null) {
			$query .= ' AND context_type = ?';
			$params[] = $contextType->value;
		}
		if ($contextId !== null) {
			$query .= ' AND context_id = ?';
			$params[] = $contextId;
		}
		if ($contextName !== null) {
			$query .= ' AND context_name = ?';
			$params[] = $contextName;
		}
		if ($tournamentId !== null) {
			$query .= ' AND tournament_id = ?';
			$params[] = $tournamentId;
		}
		$query .= ' ORDER BY id DESC';

		$result = $this->dbcn->execute_query($query, $params);
		$data = $result->fetch_all(MYSQLI_ASSOC);
		$jobs = [];
		foreach ($data as $jobData) {
			$jobs[] = $this->mapToEntity($jobData);
		}
		return $jobs;
	}

    public function findLatest(?UpdateJobType $type = null, ?UpdateJobAction $action = null, ?UpdateJobStatus $status = null, ?UpdateJobContextType $contextType = null, ?int $contextId = null, ?string $contextName = null, ?int $tournamentId = null): ?UpdateJob {
        $jobs = $this->findAll($type, $action, $status, $contextType, $contextId, $contextName, $tournamentId);
        if (count($jobs) === 0) return null;
        usort($jobs, fn(UpdateJob $a, UpdateJob $b) => $b->updatedAt <=> $a->updatedAt);
        return $jobs[0];
    }

	/**
	 * finde alle Jobs, die älter sind als 2 Wochen, und eine Nachricht enthalten
	 */
	public function findOldJobsWithMessage(): array	 {
		$query = 'SELECT * FROM update_jobs WHERE message IS NOT NULL AND updated_at < NOW() - INTERVAL 14 DAY';
		$result = $this->dbcn->execute_query($query);
		$data = $result->fetch_all(MYSQLI_ASSOC);
		$jobs = [];
		foreach ($data as $jobData) {
			$jobs[] = $this->mapToEntity($jobData);
		}
		return $jobs;
	}

	/**
	 * Finde alle Jobs, die seit einem bestimmten Zeitpunkt gestartet wurden
	 * @param \DateTimeInterface $since
	 * @return array<UpdateJob>
	 */
	public function findStartedSince(\DateTimeInterface $since): array {
		$query = 'SELECT * FROM update_jobs WHERE started_at >= ? ORDER BY started_at';
		$result = $this->dbcn->execute_query($query, [$since->format('Y-m-d H:i:s')]);
		$data = $result->fetch_all(MYSQLI_ASSOC);
		$jobs = [];
		foreach ($data as $jobData) {
			$jobs[] = $this->mapToEntity($jobData);
		}
		return $jobs;
	}

	/**
	 * Finde alle Jobs, die seit einem bestimmten Zeitpunkt aktualisiert wurden
	 * @param \DateTimeInterface $since
	 * @return array<UpdateJob>
	 */
	public function findUpdatedSince(\DateTimeInterface $since): array {
		$query = 'SELECT * FROM update_jobs WHERE updated_at > ? ORDER BY updated_at';
		$result = $this->dbcn->execute_query($query, [$since->format('Y-m-d H:i:s')]);
		$data = $result->fetch_all(MYSQLI_ASSOC);
		$jobs = [];
		foreach ($data as $jobData) {
			$jobs[] = $this->mapToEntity($jobData);
		}
		return $jobs;
	}

	public function createJob(UpdateJobType $type, UpdateJobAction $action, ?UpdateJobContextType $contextType = null, ?int $contextId = null, ?int $tournamentId = null, ?string $contextName = null): UpdateJob {
		$query = 'INSERT INTO update_jobs (type, action, context_type, context_id, context_name, tournament_id) VALUES (?, ?, ?, ?, ?, ?)';
		$params = [$type->value, $action->value, $contextType?->value, $contextId, $contextName, $tournamentId];
		$this->dbcn->execute_query($query, $params);
		$id = $this->dbcn->insert_id;
		return $this->findById($id);
	}
	public function save(UpdateJob $job): RepositorySaveResult {
		$existingJob = $this->findById($job->id);
		$dataNew = $this->mapEntityToData($job);
		$dataOld = $this->mapEntityToData($existingJob);
		$dataChanged = array_diff_assoc($dataNew, $dataOld);
		$dataPrevious = array_diff_assoc($dataOld, $dataNew);

		if (count($dataChanged) === 0) {
			return new RepositorySaveResult(SaveResult::NOT_CHANGED);
		}

		$set = implode(", ", array_map(fn($key) => "$key = ?", array_keys($dataChanged)));
		$values = array_values($dataChanged);

		$query = "UPDATE update_jobs SET $set WHERE id = ?";
		$this->dbcn->execute_query($query, [...$values, $job->id]);

		return new RepositorySaveResult(SaveResult::UPDATED, $dataChanged, $dataPrevious);
	}
}