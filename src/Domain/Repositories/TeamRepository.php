<?php

namespace App\Domain\Repositories;

use App\Core\Logger;
use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Entities\Team;
use App\Domain\Entities\ValueObjects\RankAverage;
use App\Domain\Enums\SaveResult;

class TeamRepository extends AbstractRepository {
	use DataParsingHelpers;

	protected static array $ALL_DATA_KEYS = ["OPL_ID","name","shortName","OPL_ID_logo","last_logo_download","avg_rank_tier","avg_rank_div","avg_rank_num"];
	protected static array $REQUIRED_DATA_KEYS = ["OPL_ID","name"];
	protected static array $OPL_DATA_KEYS = ["OPL_ID", "name", "shortName", "OPL_ID_logo"];
	private array $cache = [];

	public function mapToEntity(array $data): Team {
		$data = $this->normalizeData($data);
		return new Team(
			id: (int) $data['OPL_ID'],
			name: (string) $data['name'],
			shortName: $this->stringOrNull($data['shortName']),
			logoId: $this->intOrNull($data['OPL_ID_logo']),
			lastLogoDownload: $this->DateTimeImmutableOrNull($data['last_logo_download']),
			rank: new RankAverage(
				$this->stringOrNull($data['avg_rank_tier']),
				$this->stringOrNull($data['avg_rank_div']),
				$this->floatOrNull($data['avg_rank_num']),
			)
		);
	}

	public function findById(int $teamId, bool $ignoreCache = false): ?Team {
		if (isset($this->cache[$teamId]) && !$ignoreCache) {
			return $this->cache[$teamId];
		}
		$result = $this->dbcn->execute_query("SELECT * FROM teams WHERE OPL_ID = ?", [$teamId]);
		$data = $result->fetch_assoc();

		$team = $data ? $this->mapToEntity($data) : null;
		if (!$ignoreCache) $this->cache[$teamId] = $team;

		return $team;
	}

	/**
	 * @return array<Team>
	 */
	public function findAll(): array {
		$result = $this->dbcn->execute_query("SELECT * FROM teams WHERE OPL_ID > 0");
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$teams = [];
		foreach ($data as $row) {
			$teams[] = $this->mapToEntity($row);
		}
		return $teams;
	}

	/**
	 * @param string $name
	 * @return array<Team>
	 */
	public function findAllByNameContainsLetters(string $name): array {
		$name = implode("%", str_split($name));
		$result = $this->dbcn->execute_query("SELECT * FROM teams WHERE name LIKE ? OR shortName LIKE ?", ["%$name%","%$name%"]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$teams = [];
		foreach ($data as $row) {
			$teams[] = $this->mapToEntity($row);
		}
		return $teams;
	}

	public function teamExists(int $teamId): bool {
		$result = $this->dbcn->execute_query("SELECT * FROM teams WHERE OPL_ID = ?", [$teamId]);
		$data = $result->fetch_assoc();
		return $data !== null;
	}

	public function mapEntityToData(Team $team): array {
		return [
			"OPL_ID" => $team->id,
			"name" => $team->name,
			"shortName" => $team->shortName,
			"OPL_ID_logo" => $team->logoId,
			"last_logo_download" => $team->lastLogoDownload?->format("Y-m-d") ?? null,
			"avg_rank_tier" => $team->rank?->rankTier,
			"avg_rank_div" => $team->rank?->rankDiv,
			"avg_rank_num" => $team->rank?->rankNum,
		];
	}

	private function saveNewName(int $teamId, string $name, string $shortName): void {
		$nameHistoryQuery = "INSERT INTO team_name_history (OPL_ID_team, name, shortName, update_time) VALUES (?,?,?,?)";
		$this->dbcn->execute_query($nameHistoryQuery, [$teamId, $name, $shortName, date("Y-m-d H:i:s")]);
	}

	private function insert(Team $team, bool $fromOplData = false):void {
		$data = $this->mapEntityToData($team);
		if ($fromOplData) {
			$data = $this->filterKeysFromOpl($data);
		}
		$columns = implode(",", array_keys($data));
		$placeholders = implode(",", array_fill(0, count($data), "?"));
		$values = array_values($data);

		/** @noinspection SqlInsertValues */
		$teamQuery = "INSERT INTO teams ($columns) VALUES ($placeholders)";
		$this->dbcn->execute_query($teamQuery, $values);
		$this->saveNewName($team->id, $team->name, $team->shortName);

		unset($this->cache[$team->id]);
	}

	/**
	 * @param Team $team
	 * @param bool $fromOplData
	 * @return array{'result': SaveResult, 'changes': array<string, mixed>}
	 */
	private function update(Team $team, bool $fromOplData = false):array {
		$existingTeam = $this->findById($team->id, ignoreCache: true);
		$dataNew = $this->mapEntityToData($team);
		$dataOld = $this->mapEntityToData($existingTeam);
		if ($fromOplData) {
			$dataNew = $this->filterKeysFromOpl($dataNew);
			$dataOld = $this->filterKeysFromOpl($dataOld);
		}

		$dataChanged = array_diff_assoc($dataNew, $dataOld);
		$dataPrevious = array_diff_assoc($dataOld, $dataNew);

		if (count($dataChanged) === 0) {
			return ['result'=>SaveResult::NOT_CHANGED];
		}

		$set = implode(",", array_map(fn($key) => "$key = ?", array_keys($dataChanged)));
		$values = array_values($dataChanged);

		$query = "UPDATE teams SET $set WHERE OPL_ID = ?";
		$this->dbcn->execute_query($query, [...$values, $team->id]);
		if (array_key_exists("name", $dataChanged) || array_key_exists("shortName", $dataChanged)) {
			$this->saveNewName($team->id, $team->name, $team->shortName);
		}

		unset($this->cache[$team->id]);
		return ['result'=>SaveResult::UPDATED, 'changes'=>$dataChanged, 'previous'=>$dataPrevious];
	}

	/**
	 * @param Team $team
	 * @param bool $fromOplData
	 * @return array{'result': SaveResult, 'changes': ?array<string, mixed>, 'team': ?Team}
	 */
	public function save(Team $team, bool $fromOplData = false): array {
		$saveResult = [];
		try {
			if ($this->teamExists($team->id)) {
				$saveResult = $this->update($team, $fromOplData);
			} else {
				$this->insert($team, $fromOplData);
				$saveResult = ['result'=>SaveResult::INSERTED];
			}
		} catch (\Throwable $e) {
			Logger::log('db', "Fehler beim Speichern von Team $team->id: ".$e->getMessage() . "\n" . $e->getTraceAsString());
			$saveResult = ['result'=>SaveResult::FAILED];
		}
		$saveResult['team'] = $this->findById($team->id);
		return $saveResult;
	}

	public function createFromOplData(array $oplData): Team {
		$logoUrl = $oplData["logo_array"]["background"] ?? null;
		$logoId = ($logoUrl != null) ? explode("/", $logoUrl, -1) : null;
		$logoId = ($logoId != null) ? (int) end($logoId) : null;
		if ($oplData["ID"] < 0) {
			$logoId = null;
		}

		$entityData = [
			"OPL_ID" => $oplData["ID"],
			"name" => $oplData["name"],
			"shortName" => $oplData["short_name"],
			"OPL_ID_logo" => $logoId
		];
		return $this->mapToEntity($entityData);
	}

	/**
	 * @param int $teamId
	 * @return int Key (Name) of new Directory
	 */
	public function createNewLogoDirForTeam(int $teamId): int {
		$currentLogo = $this->dbcn->execute_query("SELECT * FROM team_logo_history WHERE OPL_ID_team = ? AND dir_key = -1", [$teamId])->fetch_assoc();
		$latestLogo = $this->dbcn->execute_query("SELECT * FROM team_logo_history WHERE OPL_ID_team = ? ORDER BY dir_key DESC LIMIT 1", [$teamId])->fetch_assoc();
		if ($currentLogo == null) {
			return false;
		}

		$newLogoDirKey = $latestLogo["dir_key"] + 1;
		$this->dbcn->execute_query("UPDATE team_logo_history SET dir_key = ? WHERE OPL_ID_team = ? AND dir_key = -1", [$newLogoDirKey, $teamId]);

		return $newLogoDirKey;
	}
	public function setNewLogoForTeam(int $teamId, float|null $diff): bool {
		$currentLogo = $this->dbcn->execute_query("SELECT * FROM team_logo_history WHERE OPL_ID_team = ? AND dir_key = -1", [$teamId])->fetch_assoc();
		if ($currentLogo != null) {
			return false;
		}

		$this->setLogoDownloadTimeForTeam($teamId);
		$this->dbcn->execute_query("INSERT INTO team_logo_history (OPL_ID_team, dir_key, update_time, diff_to_prev) VALUES (?, -1, ?, ?)", [$teamId, date("Y-m-d"), $diff]);

		return true;
	}
	public function setLogoDownloadTimeForTeam(int $teamId): void {
		$this->dbcn->execute_query("UPDATE teams SET last_logo_download = ? WHERE OPL_ID = ?", [date("Y-m-d H:i:s"), $teamId]);
	}
}