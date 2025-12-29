<?php

namespace App\UI\Components\Admin;

use App\Domain\Repositories\TournamentRepository;

class RelatedTournamentButtonList {
	private TournamentRepository $tournamentRepo;
	public function __construct(
		private array $idOrData
	) {
		$this->tournamentRepo = new TournamentRepository();
	}

	public function render(): string {
		$buttons = '';
		foreach ($this->idOrData as $idOrData) {
			if (is_int($idOrData)) {
				$buttons .= new RelatedTournamentButton($idOrData);
			} else {
				$tournament = $this->tournamentRepo->buildTournament($idOrData["entityData"], newEntity: true);
				$buttons .= new RelatedTournamentButton($tournament, $idOrData["relatedTournaments"]["children"]??[], $idOrData["relatedTournaments"]["parents"]??[]);
			}
		}

		return "<div class='related-event-button-list'>$buttons</div>";
	}

	public function __toString(): string {
		return $this->render();
	}
}