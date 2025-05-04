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
			$player = $this->playerRepo->mapToEntity($data);
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
}