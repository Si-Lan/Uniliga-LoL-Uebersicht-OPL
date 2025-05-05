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

	public function mapToEntity(array $data, ?Tournament $tournament=null, ?Team $team1=null, ?Team $team2=null): Matchup {
		$data = $this->normalizeData($data);
		if (is_null($team1)) {
			$team1 = $this->teamRepo->findById($data['OPL_ID_team1']);
		}
		if (is_null($team2)) {
			$team2 = $this->teamRepo->findById($data['OPL_ID_team2']);
		}
		if (is_null($tournament)) {
			$tournament = $this->tournamentRepo->findById($data['OPL_ID_tournament']);
		}
		return new Matchup(
			id: (int) $data["OPL_ID"],
			tournamentStage: $tournament,
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
}