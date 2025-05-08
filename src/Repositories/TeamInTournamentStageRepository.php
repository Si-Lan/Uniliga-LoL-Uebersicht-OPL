<?php

namespace App\Repositories;

use App\Entities\Team;
use App\Entities\Tournament;
use App\Entities\TeamInTournamentStage;
use App\Utilities\DataParsingHelpers;

class TeamInTournamentStageRepository extends AbstractRepository {
	use DataParsingHelpers;

	private TeamRepository $teamRepo;
	private TournamentRepository $tournamentRepo;
	private TeamInTournamentRepository $teamInTournamentRepo;
	protected static array $ALL_DATA_KEYS = ["OPL_ID_team","OPL_ID_group","standing","played","wins","draws","losses","points","single_wins","single_losses"];
	protected static array $REQUIRED_DATA_KEYS = ["OPL_ID_team","OPL_ID_group"];

	public function __construct() {
		parent::__construct();
		$this->teamRepo = new TeamRepository();
		$this->tournamentRepo = new TournamentRepository();
		$this->teamInTournamentRepo = new TeamInTournamentRepository();
	}

	public function mapToEntity(array $data, ?Team $team=null, ?Tournament $tournamentStage=null): TeamInTournamentStage {
		$data = $this->normalizeData($data);
		if (is_null($team)) {
			if ($this->teamRepo->dataHasAllFields($data)) {
				$team = $this->teamRepo->mapToEntity($data);
			} else {
				$team = $this->teamRepo->findById($data['OPL_ID_team']);
			}
		}
		if (is_null($tournamentStage)) {
			$tournamentStage = $this->tournamentRepo->findById($data['OPL_ID_group']);
		}
		$teamInRootTournament = $this->teamInTournamentRepo->findByTeamAndTournament($team,$tournamentStage->rootTournament);
		return new TeamInTournamentStage(
			team: $team,
			tournamentStage: $tournamentStage,
			teamInRootTournament: $teamInRootTournament,
			standing: $this->intOrNull($data['standing']),
			played: $this->intOrNull($data['played']),
			wins: $this->intOrNull($data['wins']),
			draws: $this->intOrNull($data['draws']),
			losses: $this->intOrNull($data['losses']),
			points: $this->intOrNull($data['points']),
			singleWins: $this->intOrNull($data['single_wins']),
			singleLosses: $this->intOrNull($data['single_losses']),
		);
	}

	public function findByTeamIdAndTournamentId(int $teamId, int $tournamentId): ?TeamInTournamentStage {
		$query = '
			SELECT *
				FROM teams t
				LEFT JOIN teams_in_tournament_stages tits ON t.OPL_ID = tits.OPL_ID_team AND tits.OPL_ID_group = ?
				WHERE t.OPL_ID = ?';
		$result = $this->dbcn->execute_query($query, [$tournamentId, $teamId]);
		$data = $result->fetch_assoc();

		return $data ? $this->mapToEntity($data) : null;
	}

	/**
	 * @param Tournament $tournamentStage
	 * @param bool $orderByStandings
	 * @return array<TeamInTournamentStage>|null
	 */
	public function findAllByTournamentStage(Tournament $tournamentStage, bool $orderByStandings = true): ?array {
		$query = '
			SELECT *
			FROM teams t 
			LEFT JOIN teams_in_tournament_stages tits ON t.OPL_ID = tits.OPL_ID_team
			WHERE tits.OPL_ID_group = ?
				AND t.OPL_ID > -1';
		if ($orderByStandings) {
			$query .= ' ORDER BY IF((standing=0 OR standing IS NULL), 1, 0), standing, single_wins DESC, single_losses, name';
		}
		$result = $this->dbcn->execute_query($query,[$tournamentStage->id]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$teams = [];
		foreach ($data as $team) {
			$teams[] = $this->mapToEntity($team, tournamentStage: $tournamentStage);
		}
		return $teams;
	}
}