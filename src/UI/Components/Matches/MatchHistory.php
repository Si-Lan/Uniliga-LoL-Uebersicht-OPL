<?php

namespace App\UI\Components\Matches;

use App\Domain\Entities\TeamInTournament;
use App\Domain\Entities\Tournament;
use App\Domain\Repositories\GameInMatchRepository;
use App\Domain\Repositories\MatchupRepository;
use App\Domain\Repositories\PlayerInTeamInTournamentRepository;
use App\Domain\Repositories\PlayerSeasonRankRepository;
use App\UI\Components\Games\GameDetails;

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
			$games = $this->gameInMatchRepo->findAllActiveByMatchup($matchup);
			if ($index != 0) {
				echo "<div class='divider rounds'></div>";
			}
			echo "<div id='$matchup->id' class='round-wrapper'>";
			echo new MatchRound($matchup);
			if (count($games) == 0) {
				if ($matchup->isQualified()) {
					echo "<div class='no-game-found'>Match wird nicht gespielt (Beide Teams qualifiziert)</div>";
				} elseif ($matchup->defWin) {
					echo "<div class='no-game-found'>Keine Spieldaten vorhanden (Default Win)</div>";
				} else {
					echo "<div class='no-game-found'>Noch keine Spieldaten gefunden</div>";
				}
			}
			foreach ($games as $gameInMatchup) {
				echo new GameDetails($gameInMatchup,$this->teamInTournament->team,$this->playerInTeamInTournamentRepo, $this->playerSeasonRankRepo);
			}
			echo "</div>";
		}
		return ob_get_clean();
	}

	public function __toString(): string {
		return $this->render();
	}
}