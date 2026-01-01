<?php

namespace App\Domain\Repositories;

use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Entities\Player;
use App\Domain\Entities\PlayerInTournament;
use App\Domain\Entities\Tournament;
use App\Domain\Enums\SaveResult;

class PlayerInTournamentRepository extends AbstractRepository {
	use DataParsingHelpers;

	private PlayerRepository $playerRepo;
	private TournamentRepository $tournamentRepo;
	protected static array $ALL_DATA_KEYS = ["OPL_ID","name","riotID_name","riotID_tag","summonerName","summonerID","PUUID","rank_tier","rank_div","rank_LP","matchesGotten","OPL_ID_tournament","roles","champions"];
	protected static array $REQUIRED_DATA_KEYS = ["OPL_ID","name","OPL_ID_tournament"];

	public function __construct() {
		parent::__construct();
		$this->playerRepo = new PlayerRepository();
		$this->tournamentRepo = new TournamentRepository();
	}

	public function mapToEntity(array $data, ?Player $player = null, ?Tournament $tournament = null): PlayerInTournament {
		$data = $this->normalizeData($data);
		if (is_null($player)) {
			$player = $this->playerRepo->mapToEntity($data);
		}
		if (is_null($tournament)) {
			$tournament = $this->tournamentRepo->findById($data['OPL_ID_tournament']??null);
		}
		return new PlayerInTournament(
			player: $player,
			tournament: $tournament,
			roles: $this->decodeJsonOrDefault($data['roles'],'{"top":0,"jungle":0,"middle":0,"bottom":0,"utility":0}'),
			champions: $this->decodeJsonOrDefault($data['champions'], "[]")
		);
	}
    public function mapEntityToStatsData(PlayerInTournament $playerInTournament): array {
        return [
            "OPL_ID_player" => $playerInTournament->player->id,
            "OPL_ID_tournament" => $playerInTournament->tournament->id,
            "roles" => json_encode($playerInTournament->stats->roles),
            "champions" => json_encode($playerInTournament->stats->champions),
        ];
    }

	public function findByPlayerIdAndTournamentId(int $playerId, int $tournamentId): ?PlayerInTournament {
		$query = '
			SELECT *
				FROM players p
				LEFT JOIN stats_players_in_tournaments spit ON p.OPL_ID = spit.OPL_ID_player AND spit.OPL_ID_tournament = ?
				WHERE p.OPL_ID = ?';
		$result = $this->dbcn->execute_query($query, [$tournamentId,$playerId]);
		$data = $result->fetch_assoc();
        if ($data['OPL_ID_tournament'] === null) {
            $data['OPL_ID_tournament'] = $tournamentId;
        }
		return $data ? $this->mapToEntity($data) : null;
	}

	/**
	 * @param Tournament $tournament
	 * @return array<PlayerInTournament>
	 */
	public function findAllByTournament(Tournament $tournament): array {
		$query = '
			SELECT *
			FROM players p
			    LEFT JOIN stats_players_in_tournaments spit ON p.OPL_ID = spit.OPL_ID_player AND spit.OPL_ID_tournament = ?
			    JOIN players_in_teams_in_tournament pitit ON p.OPL_ID = pitit.OPL_ID_player AND pitit.OPL_ID_tournament = ?';
		$result = $this->dbcn->execute_query($query, [$tournament->getRootTournament()->id, $tournament->getRootTournament()->id]);
		$data = $result->fetch_all(MYSQLI_ASSOC);
		$players = [];
		foreach ($data as $playerData) {
			$players[] = $this->mapToEntity($playerData);
		}
		return $players;
	}

    public function statsExist(PlayerInTournament $playerInTournament): bool {
        $query = 'SELECT * FROM stats_players_in_tournaments WHERE OPL_ID_player = ? AND OPL_ID_tournament = ?';
        $result = $this->dbcn->execute_query($query, [$playerInTournament->player->id, $playerInTournament->tournament->id]);
        return $result->num_rows > 0;
    }

    private function insertStats(PlayerInTournament $playerInTournament): void {
        $data = $this->mapEntityToStatsData($playerInTournament);
        $columns = implode(", ", array_keys($data));
        $placeholders = implode(", ", array_fill(0, count($data), "?"));
        $values = array_values($data);

        $query = "INSERT INTO stats_players_in_tournaments ($columns) VALUES ($placeholders)";
        $this->dbcn->execute_query($query, $values);
    }

    private function updateStats(PlayerInTournament $playerInTournament): array {
        $existingPlayerInTournament = $this->findByPlayerIdAndTournamentId($playerInTournament->player->id, $playerInTournament->tournament->id);
        $dataNew = $this->mapEntityToStatsData($playerInTournament);
        $dataOld = $this->mapEntityToStatsData($existingPlayerInTournament);
        $dataChanged = array_diff_assoc($dataNew, $dataOld);
        $dataPrevious = array_diff_assoc($dataOld, $dataNew);

        if (count($dataChanged) == 0) {
            return ['result' => SaveResult::NOT_CHANGED];
        }

        $set = implode(", ", array_map(fn($key) => "$key = ?", array_keys($dataChanged)));
        $values = array_values($dataChanged);

        $query = "UPDATE stats_players_in_tournaments SET $set WHERE OPL_ID_player = ? AND OPL_ID_tournament = ?";
        $this->dbcn->execute_query($query, [...$values, $playerInTournament->player->id, $playerInTournament->tournament->id]);

        return ['result' => SaveResult::UPDATED, 'changes' => $dataChanged, 'previous' => $dataPrevious];
    }

    public function saveStats(PlayerInTournament $playerInTournament): array {
        try {
            if ($this->statsExist($playerInTournament)) {
                $saveResult = $this->updateStats($playerInTournament);
            } else {
                $this->insertStats($playerInTournament);
                $saveResult = ['result' => SaveResult::INSERTED];
            }
        } catch (\Throwable $e) {
            $this->logger->error("Fehler beim Speichern von Spieler-In-Turnier-Statistiken: " . $e->getMessage() . $e->getTraceAsString());
            return ['result' => SaveResult::FAILED];
        }

        $saveResult['playerInTournament'] = $this->findByPlayerIdAndTournamentId($playerInTournament->player->id, $playerInTournament->tournament->id);
        return $saveResult;
    }
}