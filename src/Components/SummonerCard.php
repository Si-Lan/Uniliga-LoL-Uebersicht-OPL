<?php

namespace App\Components;

use App\Entity\PlayerInTeamInTournament;
use App\Repository\PlayerInTeamInTournamentRepository;

include_once dirname(__DIR__)."/functions/helper.php";

class SummonerCard {
	private array $tournament;
	private PlayerInTeamInTournament $playerTT;
	private array $player_rank;
	private string $current_split;
	private string $latest_patch;
	private bool $collapsed;
	public function __construct(
		\mysqli $dbcn,
		int $playerID,
		int $tournamentID,
		int $teamID,
	) {
		// TODO: Vieles hier sollte noch optimiert werden, wenn die Datenbank Zugriffe optimiert sind, aktuell will ich erstmal die grundlegende Funktionalität aus der alten Funktion übertragen
		$this->tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?",[$tournamentID])->fetch_assoc();
		$season_1 = $this->tournament["ranked_season"];
		$split_1 = $this->tournament["ranked_split"];
		$playerTTRepo = new PlayerInTeamInTournamentRepository();
		$this->playerTT = $playerTTRepo->findByPlayerAndTeamAndTournament($playerID, $teamID, $tournamentID);
		$player_rank = $dbcn->execute_query("SELECT * FROM players_season_rank WHERE OPL_ID_player = ? AND season = ? AND split = ?", [$playerID, $season_1, $split_1])->fetch_assoc();
		$next_split = get_second_ranked_split_for_tournament($dbcn,$tournamentID);
		$season_2 = $next_split["season"] ?? null;
		$split_2 = $next_split["split"] ?? null;
		$player_rank_2 = $dbcn->execute_query("SELECT * FROM players_season_rank WHERE OPL_ID_player = ? AND season = ? AND split = ?", [$playerID, $season_2, $split_2])->fetch_assoc();
		$this->current_split = get_current_ranked_split($dbcn, $tournamentID);
		$this->player_rank = [$player_rank, $player_rank_2];
		$patches = $dbcn->execute_query("SELECT patch FROM local_patches WHERE data IS TRUE AND champion_webp IS TRUE AND item_webp IS TRUE AND runes_webp IS TRUE AND spell_webp IS TRUE")->fetch_all();
		$patches = array_merge(...$patches);
		usort($patches, "version_compare");
		$this->latest_patch = end($patches);
		$this->collapsed = summonercards_collapsed();
	}

	public function render(): string
	{
		$tournament = $this->tournament;
		$playerTT = $this->playerTT;
		$player_rank = $this->player_rank;
		$current_split = $this->current_split;
		$latest_patch = $this->latest_patch;
		$collapsed = $this->collapsed;

		ob_start();
		include dirname(__DIR__,2).'/resources/components/summoner-card.php';
		return ob_get_clean();
	}
}