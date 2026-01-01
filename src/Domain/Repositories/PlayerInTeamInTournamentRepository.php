<?php

namespace App\Domain\Repositories;

use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Entities\Player;
use App\Domain\Entities\PlayerInTeamInTournament;
use App\Domain\Entities\Team;
use App\Domain\Entities\TeamInTournament;
use App\Domain\Entities\Tournament;
use App\Domain\Enums\SaveResult;

class PlayerInTeamInTournamentRepository extends AbstractRepository {
	use DataParsingHelpers;

	private PlayerRepository $playerRepo;
	private TeamInTournamentRepository $teamInTournamentRepo;
	protected static array $ALL_DATA_KEYS = ["OPL_ID","name","riotID_name","riotID_tag","summonerName","summonerID","PUUID","rank_tier","rank_div","rank_LP","matchesGotten","OPL_ID_team","OPL_ID_tournament","removed","roles","champions"];
	protected static array $REQUIRED_DATA_KEYS = ["OPL_ID","name","OPL_ID_team","OPL_ID_tournament"];
	/**
	 * @var array<string,PlayerInTeamInTournament> $cache
	 */
	private array $cache = [];
	/**
	 * @var PlayerInTeamInTournament $cache
	 */
	private array $teamCache = [];

	public function __construct() {
		parent::__construct();
		$this->playerRepo = new PlayerRepository();
		$this->teamInTournamentRepo = new TeamInTournamentRepository();
	}

	public function mapToEntity(array $data, ?Player $player = null, ?TeamInTournament $teamInTournament = null, ?Team $team = null, ?Tournament $tournament = null): PlayerInTeamInTournament {
		$data = $this->normalizeData($data);
		if (is_null($player)) {
			if ($this->playerRepo->dataHasAllFields($data)) {
				$player = $this->playerRepo->mapToEntity($data);
			} else {
				$player = $this->playerRepo->findById($data["OPL_ID"]);
			}
		}
		if (is_null($teamInTournament)) {
			if (!is_null($team) && !is_null($tournament)) {
				$teamInTournament = $this->teamInTournamentRepo->findByTeamAndTournament($team, $tournament);
			} elseif (!is_null($team)) {
				$teamInTournament = $this->teamInTournamentRepo->findByTeamAndTournamentId($team, $data['OPL_ID_tournament']??null);
			} elseif (!is_null($tournament)) {
				$teamInTournament = $this->teamInTournamentRepo->findByTeamIdAndTournament($data['OPL_ID_team']??null, $tournament);
			} else {
				$teamInTournament = $this->teamInTournamentRepo->findByTeamIdAndTournamentId($data['OPL_ID_team']??null, $data['OPL_ID_tournament']??null);
			}
		}
		return new PlayerInTeamInTournament(
			player: $player,
			teamInTournament: $teamInTournament,
			removed: (bool) $data['removed']??false,
			roles: $this->decodeJsonOrDefault($data['roles'],'{"top":0,"jungle":0,"middle":0,"bottom":0,"utility":0}'),
			champions: $this->decodeJsonOrDefault($data['champions'], "[]")
		);
	}
    public function mapEntityToStatsData(PlayerInTeamInTournament $playerInTeamInTournament): array {
        return [
            "OPL_ID_player" => $playerInTeamInTournament->player->id,
            "OPL_ID_team" => $playerInTeamInTournament->teamInTournament->team->id,
            "OPL_ID_tournament" => $playerInTeamInTournament->teamInTournament->tournament->id,
            "roles" => json_encode($playerInTeamInTournament->stats->roles),
            "champions" => json_encode($playerInTeamInTournament->stats->champions),
        ];
    }

	public function findInternal(int $playerId, int $teamId, int $tournamentId, ?Player $player=null, ?TeamInTournament $teamInTournament=null, ?Team $team=null, ?Tournament $tournament=null): ?PlayerInTeamInTournament {
		$cacheKey = $playerId."_".$teamId."_".$tournamentId;
		if (isset($this->cache[$cacheKey])) {
			return $this->cache[$cacheKey];
		}
		$query = '
			SELECT *
				FROM players p
				JOIN players_in_teams_in_tournament pitt ON p.OPL_ID = pitt.OPL_ID_player AND pitt.OPL_ID_tournament = ? AND pitt.OPL_ID_team = ?
				LEFT JOIN stats_players_teams_tournaments spit ON p.OPL_ID = spit.OPL_ID_player AND pitt.OPL_ID_team = spit.OPL_ID_team AND pitt.OPL_ID_tournament = spit.OPL_ID_tournament
				WHERE p.OPL_ID = ?';
		$result = $this->dbcn->execute_query($query, [$tournamentId, $teamId, $playerId]);
		$playerdata = $result->fetch_assoc();

		$playerInTeamInTournament = $playerdata ? $this->mapToEntity($playerdata, $player, $teamInTournament, $team, $tournament) : null;
		$this->cache[$cacheKey] = $playerInTeamInTournament;

		return $playerInTeamInTournament;
	}

	public function findByPlayerIdAndTeamIdAndTournamentId(int $playerId, int $teamId, int $tournamentId): ?PlayerInTeamInTournament {
		return $this->findInternal($playerId, $teamId, $tournamentId);
	}
	public function findByPlayerAndTeamAndTournament(Player $player, Team $team, Tournament $tournament): ?PlayerInTeamInTournament {
		return $this->findInternal($player->id, $team->id, $tournament->id, $player, team: $team, tournament: $tournament);
	}
	public function findByPlayerAndTeamInTournament(Player $player, TeamInTournament $teamInTournament): ?PlayerInTeamInTournament {
		return $this->findInternal($player->id, $teamInTournament->team->id, $teamInTournament->tournament->id, $player, $teamInTournament);
	}

	public function findAllInternal(int $teamId, int $tournamentId, ?TeamInTournament $teamInTournament = null, ?Team $team = null, ?Tournament $tournament = null): array {
		$cacheKey = $teamId."_".$tournamentId;
		if (isset($this->teamCache[$cacheKey])) {
			return $this->teamCache[$cacheKey];
		}
		$query = '
			SELECT *, pitt.OPL_ID_team, pitt.OPL_ID_tournament
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
			$players[] = $this->mapToEntity($playerData, teamInTournament: $teamInTournament, team: $team, tournament: $tournament);
		}
		$this->teamCache[$cacheKey] = $players;

		return $players;
	}

	/**
	 * @return array<PlayerInTeamInTournament>
	 */
	public function findAllByTeamInTournament(TeamInTournament $teamInTournament): array {
		return $this->findAllInternal($teamInTournament->team->id, $teamInTournament->tournament->id, teamInTournament: $teamInTournament);
	}
	/**
	 * @return array<PlayerInTeamInTournament>
	 */
	public function findAllByTeamAndTournament(Team $team, Tournament $tournament): array {
		return $this->findAllInternal($team->id, $tournament->id, team: $team, tournament: $tournament);
	}
	/**
	 * @return array<PlayerInTeamInTournament>
	 */
	public function findAllByTeamIdAndTournamentId(int $teamId, int $tournamentId): array {
		return $this->findAllInternal($teamId, $tournamentId);
	}

	/**
	 * @return array<PlayerInTeamInTournament>
	 */
	public function findAllByTeamAndTournamentAndActiveStatus(Team $team, Tournament $tournament, bool $active): array {
		$allPlayers = $this->findAllByTeamAndTournament($team, $tournament);
		return array_filter($allPlayers, fn(PlayerInTeamInTournament $player) => $player->removed === !$active);
	}

	/**
	 * @param Player $player
	 * @return array<PlayerInTeamInTournament>
	 */
	public function findAllByPlayer(Player $player): array {
		$query = '
			SELECT *, pitt.OPL_ID_team, pitt.OPL_ID_tournament
			FROM players p
			    JOIN players_in_teams_in_tournament pitt
			        ON p.OPL_ID = pitt.OPL_ID_player
				LEFT JOIN stats_players_teams_tournaments spitt
				    ON p.OPL_ID = spitt.OPL_ID_player AND spitt.OPL_ID_team = pitt.OPL_ID_team AND spitt.OPL_ID_tournament = pitt.OPL_ID_tournament
			WHERE p.OPL_ID = ?
			ORDER BY pitt.OPL_ID_tournament DESC';
		$result = $this->dbcn->execute_query($query, [$player->id]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$players = [];
		foreach ($data as $playerData) {
			$players[] = $this->mapToEntity($playerData, player: $player);
		}

		return $players;
	}

    /**
     * @param Player $player
     * @param Tournament $tournament
     * @return array<PlayerInTeamInTournament>
     */
    public function findAllByPlayerAndTournament(Player $player, Tournament $tournament): array {
        $query = '
            SELECT *, pitt.OPL_ID_team, pitt.OPL_ID_tournament
            FROM players p
                JOIN players_in_teams_in_tournament pitt
                    ON p.OPL_ID = pitt.OPL_ID_player AND pitt.OPL_ID_tournament = ?
                LEFT JOIN stats_players_teams_tournaments spitt
                    ON p.OPL_ID = spitt.OPL_ID_player AND spitt.OPL_ID_team = pitt.OPL_ID_team AND spitt.OPL_ID_tournament = pitt.OPL_ID_tournament
            WHERE p.OPL_ID = ?
        ';
        $result = $this->dbcn->execute_query($query, [$tournament->id, $player->id]);
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $players = [];
        foreach ($data as $playerData) {
            $players[] = $this->mapToEntity($playerData, player: $player, tournament: $tournament);
        }
        return $players;
    }

	public function isPlayerInTeamInTournament(int $playerId, int $teamId, int $tournamentId, bool $activeOnly = false): bool {
		$activeQuery = $activeOnly ? 'AND removed = 0' : '';
		$result = $this->dbcn->execute_query('SELECT * FROM players_in_teams_in_tournament WHERE OPL_ID_player = ? AND OPL_ID_team = ? AND OPL_ID_tournament = ? '.$activeQuery, [$playerId, $teamId, $tournamentId]);
		return $result->num_rows > 0;
	}
	public function addPlayerToTeamInTournament(int $playerId, int $teamId, int $tournamentId):bool {
		if ($this->isPlayerInTeamInTournament($playerId, $teamId, $tournamentId, activeOnly: true)) {
			return false;
		}
		if ($this->isPlayerInTeamInTournament($playerId, $teamId, $tournamentId)) {
			$query = 'UPDATE players_in_teams_in_tournament SET removed = 0 WHERE OPL_ID_player = ? AND OPL_ID_team = ? AND OPL_ID_tournament = ?';
		} else {
			$query = 'INSERT INTO players_in_teams_in_tournament (OPL_ID_player, OPL_ID_team, OPL_ID_tournament) VALUES (?, ?, ?)';
		}
		return $this->dbcn->execute_query($query, [$playerId, $teamId, $tournamentId]);
	}
	public function removePlayerFromTeamInTournament(int $playerId, int $teamId, int $tournamentId):bool {
		if (!$this->isPlayerInTeamInTournament($playerId, $teamId, $tournamentId, activeOnly: true)) {
			return false;
		}
		$query = 'UPDATE players_in_teams_in_tournament SET removed = 1 WHERE OPL_ID_player = ? AND OPL_ID_team = ? AND OPL_ID_tournament = ?';
		return $this->dbcn->execute_query($query, [$playerId, $teamId, $tournamentId]);
	}
    public function statsExist(PlayerInTeamInTournament $playerInTeamInTournament): bool {
        $query = 'SELECT * FROM stats_players_teams_tournaments WHERE OPL_ID_player = ? AND OPL_ID_team = ? AND OPL_ID_tournament = ?';
        $result = $this->dbcn->execute_query($query, [$playerInTeamInTournament->player->id, $playerInTeamInTournament->teamInTournament->team->id, $playerInTeamInTournament->teamInTournament->tournament->id]);
        return $result->num_rows > 0;
    }
    private function insertStats(PlayerInTeamInTournament $playerInTeamInTournament): void {
        $data = $this->mapEntityToStatsData($playerInTeamInTournament);
        $columns = implode(", ", array_keys($data));
        $placeholders = implode(", ", array_fill(0, count($data), "?"));
        $values = array_values($data);

        $query = "INSERT INTO stats_players_teams_tournaments ($columns) VALUES ($placeholders)";
        $this->dbcn->execute_query($query, $values);
    }
    private function updateStats(PlayerInTeamInTournament $playerInTeamInTournament): array {
        $existingPlayerInTeamInTournament = $this->findByPlayerIdAndTeamIdAndTournamentId($playerInTeamInTournament->player->id, $playerInTeamInTournament->teamInTournament->team->id, $playerInTeamInTournament->teamInTournament->tournament->id);
        $dataNew = $this->mapEntityToStatsData($playerInTeamInTournament);
        $dataOld = $this->mapEntityToStatsData($existingPlayerInTeamInTournament);
        $dataChanges = array_diff_assoc($dataNew, $dataOld);
        $dataPrevious = array_diff_assoc($dataOld, $dataNew);

        if (count($dataChanges) == 0) {
            return ['result' => SaveResult::NOT_CHANGED];
        }

        $set = implode(", ", array_map(fn($key) => "$key = ?", array_keys($dataChanges)));
        $values = array_values($dataChanges);

        $query = "UPDATE stats_players_teams_tournaments SET $set WHERE OPL_ID_player = ? AND OPL_ID_team = ? AND OPL_ID_tournament = ?";;
        $this->dbcn->execute_query($query, [...$values, $playerInTeamInTournament->player->id, $playerInTeamInTournament->teamInTournament->team->id, $playerInTeamInTournament->teamInTournament->tournament->id]);

        return ['result' => SaveResult::UPDATED, 'changes' => $dataChanges, 'previous' => $dataPrevious];
    }

    public function saveStats(PlayerInTeamInTournament $playerInTeamInTournament): array {
        try {
            if ($this->statsExist($playerInTeamInTournament)) {
                $saveResult = $this->updateStats($playerInTeamInTournament);
            } else {
                $this->insertStats($playerInTeamInTournament);
                $saveResult = ['result' => SaveResult::INSERTED];
            }
        } catch (\Throwable $e) {
            $this->logger->error("Fehler beim Speichern der Spieler-In-Team Statistiken: " . $e->getMessage(). "\n" . $e->getTraceAsString());
            return ['result' => SaveResult::FAILED];
        }

        $saveResult['playerInTeamInTournament'] = $this->findByPlayerIdAndTeamIdAndTournamentId($playerInTeamInTournament->player->id, $playerInTeamInTournament->teamInTournament->team->id, $playerInTeamInTournament->teamInTournament->tournament->id);
        return $saveResult;
    }
}