<?php

namespace App\Components\Matches;

use App\Components\Games\GameDetails;
use App\Entities\TeamInTournament;
use App\Entities\Tournament;
use App\Repositories\GameInMatchRepository;
use App\Repositories\MatchupRepository;
use App\Repositories\PlayerInTeamInTournamentRepository;
use App\Repositories\PlayerSeasonRankRepository;

class MatchHistory {
	private array $matchups;
	private GameInMatchRepository $gameInMatchRepo;
	private PlayerInTeamInTournamentRepository $playerInTeamInTournamentRepo;
	private PlayerSeasonRankRepository $playerSeasonRankRepo;
	public function __construct(
		private TeamInTournament $teamInTournament,
		private Tournament $tournamentStage
	) {
		$matchupRepo = new MatchupRepository();
		$this->matchups = $matchupRepo->findAllByTournamentStageAndTeam($this->tournamentStage, $this->teamInTournament);

		$this->gameInMatchRepo = new GameInMatchRepository();
		$this->playerInTeamInTournamentRepo = new PlayerInTeamInTournamentRepository();
		$this->playerSeasonRankRepo = new PlayerSeasonRankRepository();
	}

	public function render(): string {
		ob_start();
		foreach ($this->matchups as $index=>$matchup) {
			if (!$matchup->played) continue;
			$games = $this->gameInMatchRepo->findAllByMatchup($matchup);
			if ($index != 0) {
				echo "<div class='divider rounds'></div>";
			}
			echo "<div id='$matchup->id' class='round-wrapper'>";
			echo new MatchRound($matchup);
			if (count($games) == 0) {
				if ($matchup->defWin) {
					echo "<div class='no-game-found'>Keine Spieldaten vorhanden (Default Win)</div>";
				} else {
					echo "<div class='no-game-found'>Noch keine Spieldaten gefunden</div>";
				}
			}
			foreach ($games as $gameInMatchup) {
				echo new GameDetails($gameInMatchup->game,$this->teamInTournament->team,$this->playerInTeamInTournamentRepo, $this->playerSeasonRankRepo);
			}
			echo "</div>";
		}
		return ob_get_clean();
	}

	public function __toString(): string {
		return $this->render();
	}
}