<?php

namespace App\UI\Components\Matches\ChangeSuggestions;

use App\Domain\Entities\Matchup;
use App\Domain\Entities\MatchupChangeSuggestions;
use App\Domain\Entities\PlayerInTeamInTournament;
use App\Domain\Enums\SuggestionStatus;
use App\Domain\Repositories\MatchupChangeSuggestionRepository;
use App\Domain\Repositories\PlayerInTeamInTournamentRepository;

class AddSuggestionPopupContent {
	private MatchupChangeSuggestionRepository $matchupChangeSuggestionRepo;
	private PlayerInTeamInTournamentRepository $playerInTeamInTournamentRepo;
	/** @var array<MatchupChangeSuggestions> */
	private array $suggestions = [];
	/** @var array<PlayerInTeamInTournament> */
	private array $team1Players = [];
	/** @var array<PlayerInTeamInTournament> */
	private array $team2Players = [];
	public function __construct(
		private Matchup $matchup
	) {
		$this->matchupChangeSuggestionRepo = new MatchupChangeSuggestionRepository();
		$this->suggestions = $this->matchupChangeSuggestionRepo->findAllByMatchupIdAndStatus($this->matchup->id, SuggestionStatus::PENDING);

		$this->playerInTeamInTournamentRepo = new PlayerInTeamInTournamentRepository();
		$this->team1Players = $this->playerInTeamInTournamentRepo->findAllByTeamInTournament($this->matchup->team1);
		$this->team2Players = $this->playerInTeamInTournamentRepo->findAllByTeamInTournament($this->matchup->team2);
	}

	public function render(): string {
		$suggestions = $this->suggestions;
		$matchup = $this->matchup;
		$team1Players = $this->team1Players;
		$team2Players = $this->team2Players;
		ob_start();
		include __DIR__.'/add-suggestion-popup-content.template.php';
		return ob_get_clean();
	}

	public function __toString(): string {
		return $this->render();
	}
}