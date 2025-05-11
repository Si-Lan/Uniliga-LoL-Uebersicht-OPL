<?php

namespace App\Repositories;

use App\Entities\Matchup;
use App\Entities\Team;
use App\Entities\Tournament;
use App\Utilities\DataParsingHelpers;

class MatchupRepository extends AbstractRepository {
	use DataParsingHelpers;

	private TournamentRepository $tournamentRepo;
	private TeamRepository $teamRepo;
	protected static array $ALL_DATA_KEYS = ["OPL_ID","OPL_ID_tournament","OPL_ID_team1","OPL_ID_team2","team1Score","team2Score","plannedDate","playday","bestOf","played","winner","loser","draw","def_win"];
	protected static array $REQUIRED_DATA_KEYS = ["OPL_ID","OPL_ID_tournament","played"];

	public function __construct() {
		parent::__construct();
		$this->tournamentRepo = new TournamentRepository();
		$this->teamRepo = new TeamRepository();
	}

	public function mapToEntity(array $data, ?Tournament $tournamentStage=null, ?Team $team1=null, ?Team $team2=null): Matchup {
		$data = $this->normalizeData($data);
		if (is_null($team1) && !is_null($data["OPL_ID_team1"])) {
			$team1 = $this->teamRepo->findById($data['OPL_ID_team1']);
		}
		if (is_null($team2) && !is_null($data["OPL_ID_team2"])) {
			$team2 = $this->teamRepo->findById($data['OPL_ID_team2']);
		}
		if (is_null($tournamentStage)) {
			$tournamentStage = $this->tournamentRepo->findById($data['OPL_ID_tournament']);
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
	private function findAllInternalByQuery(string $query, array $queryParams, ?Tournament $tournamentStage=null, ?Team $team=null): array {
		$result = $this->dbcn->execute_query($query, $queryParams);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$matchups = [];
		foreach ($data as $matchupData) {
			$team1 = ($team?->id == $matchupData["OPL_ID_team1"]) ? $team : null;
			$team2 = ($team?->id == $matchupData["OPL_ID_team2"]) ? $team : null;
			$matchups[] = $this->mapToEntity($matchupData, $tournamentStage, $team1, $team2);
		}

		return $matchups;
	}

	/**
	 * @return array<Matchup>
	 */
	public function findAllByRootTournamentAndTeam(Tournament $tournament, Team $team): array {
		$query = '
			SELECT m.*
			FROM matchups m
			    LEFT JOIN tournaments t
			        ON m.OPL_ID_tournament = t.OPL_ID
			WHERE t.OPL_ID_top_parent = ? AND (OPL_ID_team1 = ? OR OPL_ID_team2 = ?)';
		$queryParams = [$tournament->id, $team->id, $team->id];

		return $this->findAllInternalByQuery($query, $queryParams, team: $team);
	}
	/**
	 * @return array<Matchup>
	 */
	public function findAllByTournamentStageAndTeam(Tournament $tournamentStage, Team $team): array {
		$query = 'SELECT * FROM matchups WHERE OPL_ID_tournament = ? AND (OPL_ID_team1 = ? OR OPL_ID_team2 = ?)';
		$queryParams = [$tournamentStage->id, $team->id, $team->id];

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
}