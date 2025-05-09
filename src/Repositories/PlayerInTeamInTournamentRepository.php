<?php

namespace App\Repositories;

use App\Entities\Player;
use App\Entities\Team;
use App\Entities\PlayerInTeamInTournament;
use App\Entities\Tournament;
use App\Utilities\DataParsingHelpers;

class PlayerInTeamInTournamentRepository extends AbstractRepository {
	use DataParsingHelpers;

	private PlayerRepository $playerRepo;
	private TeamRepository $teamRepo;
	private TournamentRepository $tournamentRepo;
	protected static array $ALL_DATA_KEYS = ["OPL_ID","name","riotID_name","riotID_tag","summonerName","summonerID","PUUID","rank_tier","rank_div","rank_LP","matchesGotten","OPL_ID_team","OPL_ID_tournament","removed","roles","champions"];
	protected static array $REQUIRED_DATA_KEYS = ["OPL_ID","name","OPL_ID_team","OPL_ID_tournament"];

	public function __construct() {
		parent::__construct();
		$this->teamRepo = new TeamRepository();
		$this->playerRepo = new PlayerRepository();
		$this->tournamentRepo = new TournamentRepository();
	}

	public function mapToEntity(array $data, ?Player $player = null, ?Team $team = null, ?Tournament $tournament = null): PlayerInTeamInTournament {
		$data = $this->normalizeData($data);
		if (is_null($player)) {
			if ($this->playerRepo->dataHasAllFields($data)) {
				$player = $this->playerRepo->mapToEntity($data);
			} else {
				$player = $this->playerRepo->findById($data["OPL_ID"]);
			}
		}
		if (is_null($team)) {
			$team = $this->teamRepo->findById($data['OPL_ID_team']??null);
		}
		if (is_null($tournament)) {
			$tournament = $this->tournamentRepo->findById($data['OPL_ID_tournament']??null);
		}
		return new PlayerInTeamInTournament(
			player: $player,
			team: $team,
			tournament: $tournament,
			removed: (bool) $data['removed']??false,
			roles: $this->decodeJsonOrDefault($data['roles'],'{"top":0,"jungle":0,"middle":0,"bottom":0,"utility":0}'),
			champions: $this->decodeJsonOrDefault($data['champions'], "[]")
		);
	}

	public function findByPlayerIdAndTeamIdAndTournamentId(int $playerId, int $teamId, int $tournamentId): ?PlayerInTeamInTournament {
		$query = '
			SELECT *
				FROM players p
				JOIN players_in_teams_in_tournament pitt ON p.OPL_ID = pitt.OPL_ID_player AND pitt.OPL_ID_tournament = ? AND pitt.OPL_ID_team = ?
				LEFT JOIN stats_players_teams_tournaments spit ON p.OPL_ID = spit.OPL_ID_player AND pitt.OPL_ID_team = spit.OPL_ID_team AND pitt.OPL_ID_tournament = spit.OPL_ID_tournament
				WHERE p.OPL_ID = ?';
		$result = $this->dbcn->execute_query($query, [$tournamentId, $teamId, $playerId]);
		$playerdata = $result->fetch_assoc();

		return $playerdata ? $this->mapToEntity($playerdata) : null;
	}

	public function findAllInternal(int $teamId, int $tournamentId, Team $team = null, Tournament $tournament = null): array {
		$query = '
			SELECT *
			FROM players p
			    JOIN players_in_teams_in_tournament pitt
			        ON p.OPL_ID = pitt.OPL_ID_player AND pitt.OPL_ID_tournament = ? AND pitt.OPL_ID_team = ?
				LEFT JOIN stats_players_teams_tournaments spitt
				    ON p.OPL_ID = spitt.OPL_ID_player AND spitt.OPL_ID_team = pitt.OPL_ID_team AND spitt.OPL_ID_tournament = ?
			WHERE pitt.OPL_ID_team = ?';
		$result = $this->dbcn->execute_query($query, [$tournamentId, $teamId, $tournamentId, $teamId]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$players = [];
		foreach ($data as $playerData) {
			$players[] = $this->mapToEntity($playerData, team: $team, tournament: $tournament);
		}

		return $players;
	}

	/**
	 * @return array<PlayerInTeamInTournament>
	 */
	public function findAllByTeamAndTournament(Team $team, Tournament $tournament): array {
		return $this->findAllInternal($team->id, $tournament->id, $team, $tournament);
	}
	/**
	 * @return array<PlayerInTeamInTournament>
	 */
	public function findAllByTeamIdAndTournamentId(int $teamId, int $tournamentId): array {
		return $this->findAllInternal($teamId, $tournamentId);
	}
}