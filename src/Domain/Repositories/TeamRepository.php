<?php

namespace App\Domain\Repositories;

use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Entities\Team;
use App\Domain\Entities\ValueObjects\RankAverage;

class TeamRepository extends AbstractRepository {
	use DataParsingHelpers;

	protected static array $ALL_DATA_KEYS = ["OPL_ID","name","shortName","OPL_ID_logo","last_logo_download","avg_rank_tier","avg_rank_div","avg_rank_num"];
	protected static array $REQUIRED_DATA_KEYS = ["OPL_ID","name"];
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

	public function findById(int $teamId): ?Team {
		if (isset($this->cache[$teamId])) {
			return $this->cache[$teamId];
		}
		$result = $this->dbcn->execute_query("SELECT * FROM teams WHERE OPL_ID = ?", [$teamId]);
		$data = $result->fetch_assoc();

		$team = $data ? $this->mapToEntity($data) : null;
		$this->cache[$teamId] = $team;

		return $team;
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

		$this->dbcn->execute_query("UPDATE teams SET last_logo_download = ? WHERE OPL_ID = ?", [date("Y-m-d H:i:s"), $teamId]);
		$this->dbcn->execute_query("INSERT INTO team_logo_history (OPL_ID_team, dir_key, update_time, diff_to_prev) VALUES (?, -1, ?, ?)", [$teamId, date("Y-m-d"), $diff]);

		return true;
	}
}