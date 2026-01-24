<?php

namespace App\UI\Components\Admin\TournamentEdit;

use App\Domain\Entities\Tournament;
use App\Domain\Enums\EventType;
use App\Domain\Repositories\TournamentRepository;

class TournamentEditAccordion {
	public function __construct(
		private Tournament $tournament,
		private ?EventType $eventsToShow = null,
		private array $openAccordeons = [],
		private ?TournamentRepository $tournamentRepo = null
	) {
		$this->tournamentRepo = $this->tournamentRepo ?? new TournamentRepository();
	}

	public function render(): string {
		if (!$this->tournament->eventType->hasChildren()) return '';

		$events = $this->tournamentRepo->findAllByParentTournamentAndType($this->tournament, $this->eventsToShow);

		if (count($events) === 0) return '';

		$tournament = $this->tournament;
		$openAccordeons = $this->openAccordeons;

		ob_start();
		include __DIR__.'/tournament-edit-accordion.template.php';
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}
}