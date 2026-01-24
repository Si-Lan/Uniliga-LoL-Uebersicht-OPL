<?php

namespace App\UI\Components\Admin\TournamentEdit;

use App\Domain\Repositories\TournamentRepository;

class TournamentEditList {
	public function __construct(
		private array $openAccordeons = [],
		private ?TournamentRepository $tournamentRepo = null
	) {
		$this->tournamentRepo = $this->tournamentRepo ?? new TournamentRepository();
	}

	public function render(): string {
		$tournamentRepo = $this->tournamentRepo;
		$openAccordeons = $this->openAccordeons;
		ob_start();
		include __DIR__.'/tournament-edit-list.template.php';
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}
}