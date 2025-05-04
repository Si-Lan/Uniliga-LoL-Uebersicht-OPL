<?php

namespace App\Repositories;

use App\Entities\Player;
use App\Entities\Tournament;
use App\Entities\PlayerInTournament;
use App\Utilities\DataParsingHelpers;

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

	public function findByPlayerIdAndTournamentId(int $playerId, int $tournamentId): ?PlayerInTournament {
		$query = '
			SELECT *
				FROM players p
				LEFT JOIN stats_players_in_tournaments spit ON p.OPL_ID = spit.OPL_ID_player AND spit.OPL_ID_tournament = ?
				WHERE p.OPL_ID = ?';
		$result = $this->dbcn->execute_query($query, [$tournamentId,$playerId]);
		$data = $result->fetch_assoc();

		return $data ? $this->mapToEntity($data) : null;
	}
}