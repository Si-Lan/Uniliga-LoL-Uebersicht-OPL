<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\Player;
use App\Domain\Entities\PlayerInTeam;
use App\Domain\Entities\Team;

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

	/**
	 * @return array<PlayerInTeam>
	 */
	public function findAllByTeamAndActiveStatus(Team $team, bool $active): array {
		$allPlayers = $this->findAllByTeam($team);
		$filteredPlayers = array_filter($allPlayers, fn(PlayerInTeam $player) => $player->removed === !$active);
		return array_values($filteredPlayers);
	}

	public function isPlayerInTeam(int $playerId, int $teamId, bool $onlyActive = false): bool {
		$activeQuery = $onlyActive ? 'AND removed = 0' : '';
		$result = $this->dbcn->execute_query('SELECT * FROM players_in_teams WHERE OPL_ID_player = ? AND OPL_ID_team = ? '.$activeQuery, [$playerId, $teamId]);
		return $result->num_rows > 0;
	}
	public function addPlayerToTeam(int $playerId, int $teamId): bool {
		if ($this->isPlayerInTeam($playerId, $teamId, onlyActive: true)) {
			return false;
		}
		if ($this->isPlayerInTeam($playerId, $teamId)) {
			$query = 'UPDATE players_in_teams SET removed = 0 WHERE OPL_ID_player = ? AND OPL_ID_team = ?';
		} else {
			$query = 'INSERT INTO players_in_teams (OPL_ID_player, OPL_ID_team) VALUES (?, ?)';
		}
		return $this->dbcn->execute_query($query, [$playerId, $teamId]);
	}
	public function removePlayerFromTeam(int $playerId, int $teamId): bool {
		if (!$this->isPlayerInTeam($playerId, $teamId, onlyActive: true)) {
			return false;
		}
		$query = 'UPDATE players_in_teams SET removed = 1 WHERE OPL_ID_player = ? AND OPL_ID_team = ?';
		return $this->dbcn->execute_query($query, [$playerId, $teamId]);
	}
}