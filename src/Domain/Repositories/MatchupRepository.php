<?php

namespace App\Domain\Repositories;

use App\Core\Logger;
use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Entities\Matchup;
use App\Domain\Entities\TeamInTournament;
use App\Domain\Entities\Tournament;
use App\Domain\Enums\SaveResult;

class MatchupRepository extends AbstractRepository {
	use DataParsingHelpers;

	private TournamentRepository $tournamentRepo;
	private TeamInTournamentRepository $teamInTournamentRepo;
	protected static array $ALL_DATA_KEYS = ["OPL_ID","OPL_ID_tournament","OPL_ID_team1","OPL_ID_team2","team1Score","team2Score","plannedDate","playday","bestOf","played","winner","loser","draw","def_win"];
	protected static array $REQUIRED_DATA_KEYS = ["OPL_ID","OPL_ID_tournament","played"];
	protected static array $OPL_DATA_KEYS = ["OPL_ID","OPL_ID_tournament","OPL_ID_team1","OPL_ID_team2","plannedDate","playday","bestOf"];

	public function __construct() {
		parent::__construct();
		$this->tournamentRepo = new TournamentRepository();
		$this->teamInTournamentRepo = new TeamInTournamentRepository();
	}

	public function mapToEntity(array $data, ?Tournament $tournamentStage=null, ?TeamInTournament $team1=null, ?TeamInTournament $team2=null): Matchup {
		$data = $this->normalizeData($data);
		if (is_null($tournamentStage)) {
			$tournamentStage = $this->tournamentRepo->findById($data['OPL_ID_tournament']);
		}
		if (is_null($team1) && !is_null($data["OPL_ID_team1"])) {
			$team1 = $this->teamInTournamentRepo->findByTeamIdAndTournament($data['OPL_ID_team1'],$tournamentStage->rootTournament);
		}
		if (is_null($team2) && !is_null($data["OPL_ID_team2"])) {
			$team2 = $this->teamInTournamentRepo->findByTeamIdAndTournament($data['OPL_ID_team2'],$tournamentStage->rootTournament);
		}
		return new Matchup(
			id: (int) $data["OPL_ID"],
			tournamentStage: $tournamentStage,
			team1: $team1,
			team2: $team2,
			team1Score: $this->stringOrNull($data["team1Score"]),
			team2Score: $this->stringOrNull($data["team2Score"]),
			plannedDate: $this->DateTimeImmutableOrNull($data["plannedDate"]),
			playday: $this->intOrNull($data["playday"]),
			bestOf: $this->intOrNull($data["bestOf"]),
			played: (bool) $data["played"],
			winnerId: $this->intOrNull($data["winner"]),
			loserId: $this->intOrNull($data["loser"]),
			draw: (bool) $data["draw"] ?? false,
			defWin: (bool) $data["def_win"] ?? false
		);
	}

	public function findById(int $matchupId): ?Matchup {
		$query = "SELECT * FROM matchups WHERE OPL_ID = ?";
		$result = $this->dbcn->execute_query($query,[$matchupId]);
		$data = $result->fetch_assoc();

		return $data ? $this->mapToEntity($data) : null;
	}

	/**
	 * @return array<Matchup>
	 */
	private function findAllInternalByQuery(string $query, array $queryParams, ?Tournament $tournamentStage=null, ?TeamInTournament $teamInTournament=null): array {
		$result = $this->dbcn->execute_query($query, $queryParams);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$matchups = [];
		foreach ($data as $matchupData) {
			$team1 = ($teamInTournament?->team->id == $matchupData["OPL_ID_team1"]) ? $teamInTournament : null;
			$team2 = ($teamInTournament?->team->id == $matchupData["OPL_ID_team2"]) ? $teamInTournament : null;
			$matchups[] = $this->mapToEntity($matchupData, $tournamentStage, $team1, $team2);
		}

		return $matchups;
	}

	/**
	 * @return array<Matchup>
	 */
	public function findAllByRootTournamentAndTeam(Tournament $tournament, TeamInTournament $team): array {
		$query = '
			SELECT m.*
			FROM matchups m
			    LEFT JOIN tournaments t
			        ON m.OPL_ID_tournament = t.OPL_ID
			WHERE t.OPL_ID_top_parent = ? AND (OPL_ID_team1 = ? OR OPL_ID_team2 = ?)';
		$queryParams = [$tournament->id, $team->team->id, $team->team->id];

		return $this->findAllInternalByQuery($query, $queryParams, teamInTournament: $team);
	}
	/**
	 * @return array<Matchup>
	 */
	public function findAllByTournamentStageAndTeam(Tournament $tournamentStage, TeamInTournament $team): array {
		$query = 'SELECT * FROM matchups WHERE OPL_ID_tournament = ? AND (OPL_ID_team1 = ? OR OPL_ID_team2 = ?)';
		$queryParams = [$tournamentStage->id, $team->team->id, $team->team->id];

		return $this->findAllInternalByQuery($query, $queryParams, $tournamentStage, $team);
	}
	/**
	 * @return array<Matchup>
	 */
	public function findAllByTournamentStage(Tournament $tournamentStage): array {
		$query = 'SELECT * FROM matchups WHERE OPL_ID_tournament = ?';
		$queryParams = [$tournamentStage->id];
		return $this->findAllInternalByQuery($query, $queryParams, $tournamentStage);
	}
	/**
	 * @return array<Matchup>
	 */
	public function findAllWithATeamByTournamentStage(Tournament $tournamentStage): array {
		$query = '
			SELECT * 
			FROM matchups
			WHERE OPL_ID_tournament = ?
				AND NOT (
				    (OPL_ID_team1 IS NULL OR OPL_ID_team1 < 0)
					AND (OPL_ID_team2 IS NULL OR OPL_ID_team2 < 0)
				)';
		$queryParams = [$tournamentStage->id];
		return $this->findAllInternalByQuery($query, $queryParams, $tournamentStage);
	}
	/**
	 * @return array<Matchup>
	 */
	public function findAllByRootTournament(Tournament $rootTournament): array {
		$query = '
			SELECT m.*
			FROM matchups m
			    LEFT JOIN tournaments t
			        ON m.OPL_ID_tournament = t.OPL_ID
			WHERE t.OPL_ID_top_parent = ?';
		$queryParams = [$rootTournament->id];
		return $this->findAllInternalByQuery($query, $queryParams);
	}

	public function matchupExists(int $matchupId): bool {
		$query = "SELECT * FROM matchups WHERE OPL_ID = ?";
		$result = $this->dbcn->execute_query($query,[$matchupId]);
		return $result->num_rows > 0;
	}

	public function mapEntityToData(Matchup $matchup): array {
		return [
			"OPL_ID" => $matchup->id,
			"OPL_ID_tournament" => $matchup->tournamentStage->id,
			"OPL_ID_team1" => $matchup->team1?->team->id,
			"OPL_ID_team2" => $matchup->team2?->team->id,
			"team1Score" => $matchup->team1Score,
			"team2Score" => $matchup->team2Score,
			"plannedDate" => $matchup->plannedDate?->format("Y-m-d H:i:s") ?? null,
			"playday" => $matchup->playday,
			"bestOf" => $matchup->bestOf,
			"played" => $this->intOrNull($matchup->played),
			"winner" => $matchup->winnerId,
			"loser" => $matchup->loserId,
			"draw" => $this->intOrNull($matchup->draw),
			"def_win" => $this->intOrNull($matchup->defWin),
		];
	}

	private function insert(Matchup $matchup, bool $fromOplData = false): void {
		$data = $this->mapEntityToData($matchup);
		$columns = implode(",", array_keys($data));
		$placeholders = implode(",", array_fill(0, count($data), "?"));
		$values = array_values($data);

		/** @noinspection SqlInsertValues */
		$query = "INSERT INTO matchups ($columns) VALUES ($placeholders)";
		$this->dbcn->execute_query($query, $values);
	}

	private function update(Matchup $matchup, bool $fromOplData = false): array {
		$existingMatchup = $this->findById($matchup->id);

		$dataNew = $this->mapEntityToData($matchup);
		$dataOld = $this->mapEntityToData($existingMatchup);
		if ($fromOplData) {
			$dataNew = $this->filterKeysFromOpl($dataNew);
			$dataOld = $this->filterKeysFromOpl($dataOld);
		}
		$dataChanged = array_diff_assoc($dataNew, $dataOld);
		$dataPrevious = array_diff_assoc($dataOld, $dataNew);

		if (count($dataChanged) == 0) {
			return ['result' => SaveResult::NOT_CHANGED];
		}

		$set = implode(",", array_map(fn($key) => "$key = ?", array_keys($dataChanged)));
		$values = array_values($dataChanged);

		$query = "UPDATE matchups SET $set WHERE OPL_ID = ?";
		$this->dbcn->execute_query($query, [...$values, $matchup->id]);

		return ['result' => SaveResult::UPDATED, 'changes'=>$dataChanged, 'previous'=>$dataPrevious];
	}

	public function save(Matchup $matchup, bool $fromOplData = false): array {
		try {
			if ($this->matchupExists($matchup->id)) {
				$saveResult = $this->update($matchup, $fromOplData);
			} else {
				$this->insert($matchup, $fromOplData);
				$saveResult = ['result'=>SaveResult::INSERTED];
			}
		} catch (\Throwable $e) {
			Logger::log('db', "Fehler beim Speichern des Matchups: " . $e->getMessage().'\n'.$e->getTraceAsString());
			$saveResult = ['result'=>SaveResult::FAILED];
		}
		$saveResult['matchup'] = $this->findById($matchup->id);
		return $saveResult;
	}

	public function delete(Matchup $matchup): bool {
		if (!$this->matchupExists($matchup->id)) {
			return false;
		}
		return $this->dbcn->execute_query("DELETE FROM matchups WHERE OPL_ID = ?", [$matchup->id]);
	}

	public function createFromOplData(array $oplData): Matchup {
		$teamIds = array_keys($oplData["teams"]);
		$team1 = null;
		$team2 = null;
		if (count($teamIds) > 0) $team1 = $oplData["teams"][$teamIds[0]]["ID"];
		if (count($teamIds) > 1) $team2 = $oplData["teams"][$teamIds[1]]["ID"];

		$entityData = [
			"OPL_ID" => $oplData["ID"],
			"OPL_ID_tournament" => $oplData["tournament"]["ID"],
			"OPL_ID_team1" => $team1,
			"OPL_ID_team2" => $team2,
			"plannedDate" => $oplData["to_be_played_on"],
			"playday" => $oplData["playday"],
			"bestOf" => $oplData["best_of"],
			"played" => false,
		];

		return $this->mapToEntity($entityData);
	}
}