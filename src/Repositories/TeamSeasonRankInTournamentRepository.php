<?php

namespace App\Repositories;

use App\Entities\RankedSplit;
use App\Entities\Team;
use App\Entities\TeamSeasonRankInTournament;
use App\Entities\Tournament;
use App\Entities\ValueObjects\RankAverage;
use App\Utilities\DataParsingHelpers;

class TeamSeasonRankInTournamentRepository extends AbstractRepository {
	use DataParsingHelpers;
	private RankedSplitRepository $rankedSplitRepo;
	private TournamentRepository $tournamentRepo;
	private TeamRepository $teamRepo;
	protected static array $ALL_DATA_KEYS = ["OPL_ID_team","OPL_ID_tournament","season","split","avg_rank_tier","avg_rank_div","avg_rank_num"];
	protected static array $REQUIRED_DATA_KEYS = ["OPL_ID_team","OPL_ID_tournament","season","split"];

	public function __construct() {
		parent::__construct();
		$this->rankedSplitRepo = new RankedSplitRepository();
		$this->tournamentRepo = new TournamentRepository();
		$this->teamRepo = new TeamRepository();
	}

	public function mapToEntity(array $data, ?Team $team, ?Tournament $tournament, ?RankedSplit $rankedSplit): TeamSeasonRankInTournament {
		$data = $this->normalizeData($data);
		if (is_null($team)) {
			$team = $this->teamRepo->findById($data['OPL_ID_team']);
		}
		if (is_null($tournament)) {
			$tournament = $this->tournamentRepo->findById($data['OPL_ID_tournament']);
		}
		if (is_null($rankedSplit)) {
			$rankedSplit = $this->rankedSplitRepo->findBySeasonAndSplit($data['season'], $data['split']);
		}
		return new TeamSeasonRankInTournament(
			team: $team,
			tournament: $tournament,
			rankedSplit: $rankedSplit,
			rank: new RankAverage(
				$this->stringOrNull($data['avg_rank_tier']),
				$this->stringOrNull($data['avg_rank_div']),
				$this->floatOrNull($data['avg_rank_num'])
			)
		);
	}

	/**
	 * @param Team|int $team Team-Objekt oder Team-Id
	 * @param Tournament|int $tournament Tournament-Objekt oder Tournament-Id
	 * @param string|RankedSplit $seasonOrRankedSplit Season oder RankedSplit-Objekt
	 * @param string|null $split Split (nur notwendig, wenn Season als String übergeben wird)
	 * @return TeamSeasonRankInTournament|null
	 */
	public function findTeamSeasonRankInTournament(Team|int $team, Tournament|int $tournament, string|RankedSplit $seasonOrRankedSplit, ?string $split = null): ?TeamSeasonRankInTournament {
		$teamId = $team instanceof Team ? $team->id : $team;
		$teamObj = $team instanceof Team ? $team : null;

		$tournamentId = $tournament instanceof Tournament ? $tournament->id : $tournament;
		$tournamentObj = $tournament instanceof Tournament ? $tournament : null;

		if ($seasonOrRankedSplit instanceof RankedSplit) {
			$season = $seasonOrRankedSplit->season;
			$split = $seasonOrRankedSplit->split;
			$rankedSplitObj = $seasonOrRankedSplit;
		} else {
			$season = $seasonOrRankedSplit;
			$rankedSplitObj = null;
			if ($split === null) {
				throw new \InvalidArgumentException("Split must be provided when no RankedSplit is given.");
			}
		}

		$query = 'SELECT * FROM teams_season_rank_in_tournament WHERE OPL_ID_team = ? AND OPL_ID_tournament = ? AND season = ? AND split = ?';
		$result = $this->dbcn->execute_query($query, [$teamId,$tournamentId,$season,$split]);
		$data = $result->fetch_assoc();

		return $data ? $this->mapToEntity($data, team: $teamObj, tournament: $tournamentObj, rankedSplit: $rankedSplitObj) : null;
	}
}