<?php

namespace App\Repositories;

use App\Entities\Player;
use App\Entities\PlayerInTeam;
use App\Entities\Team;

class PlayerInTeamRepository extends AbstractRepository {
	private PlayerRepository $playerRepo;
	private TeamRepository $teamRepo;
	protected static array $ALL_DATA_KEYS = ["OPL_ID","name","riotID_name","riotID_tag","summonerName","summonerID","PUUID","rank_tier","rank_div","rank_LP","matchesGotten","OPL_ID_team","removed"];
	protected static array $REQUIRED_DATA_KEYS = ["OPL_ID","name","OPL_ID_team"];

	public function __construct() {
		parent::__construct();
		$this->playerRepo = new PlayerRepository();
		$this->teamRepo = new TeamRepository();
	}

	public function mapToEntity(array $data, ?Player $player = null, ?Team $team = null): PlayerInTeam {
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
		return new PlayerInTeam(
			player: $player,
			team: $team,
			removed: (bool) $data['removed']??false
		);
	}

	public function findByPlayerIdAndTeamId(int $playerId, int $teamId): ?PlayerInTeam {
		$query = '
			SELECT * 
				FROM players p
				JOIN players_in_teams pit ON p.OPL_ID = pit.OPL_ID_player AND pit.OPL_ID_team = ?
				WHERE p.OPL_ID = ?';
		$result = $this->dbcn->execute_query($query, [$teamId, $playerId]);
		$data = $result->fetch_assoc();

		return $data ? $this->mapToEntity($data) : null;
	}

	public function findAllInternal(int $teamId, ?Team $team=null): array {
		$query = '
			SELECT *
			FROM players p
			    JOIN players_in_teams pit ON p.OPL_ID = pit.OPL_ID_player
			WHERE pit.OPL_ID_team = ?';
		$result = $this->dbcn->execute_query($query, [$teamId]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$players = [];
		foreach ($data as $playerData) {
			$players[] = $this->mapToEntity($playerData, team: $team);
		}

		return $players;
	}

	/**
	 * @return array<PlayerInTeam>
	 */
	public function findAllByTeam(Team $team): array {
		return $this->findAllInternal($team->id, $team);
	}
	/**
	 * @return array<PlayerInTeam>
	 */
	public function findAllByTeamId(int $teamId): array {
		return $this->findAllInternal($teamId);
	}
}