<?php

namespace App\UI\Components\Admin;

use App\Domain\Entities\Tournament;
use App\Domain\Repositories\TournamentRepository;
use App\UI\Components\Admin\TournamentEdit\TournamentEditForm;
use App\UI\Components\Popups\Popup;

class RelatedTournamentButton {
	private bool $inDatabase = false;
	public function __construct(
		private Tournament|int $tournament,
		private array $childrenIds = [],
		private array $parentIds = [],
	) {
		if ($tournament instanceof Tournament) {
			$tournamentRepo = new TournamentRepository();
			$this->inDatabase = $tournamentRepo->tournamentExists($this->tournament->id);
		}
	}

	public function render(): string {
		if (!($this->tournament instanceof Tournament)) {
			return "<button data-tournament-id='{$this->tournament}' data-dialog-id='tournament-add-{$this->tournament}' class='related-tournament-button' disabled>{$this->tournament}</button>";
		}

		$disabledAttr = $this->inDatabase ? 'disabled' : '';
		$classes = implode(' ', array_filter(['related-tournament-button', $this->inDatabase ? 'in-db' : '']));
		$button = "<button data-tournament-id='{$this->tournament->id}' data-dialog-id='tournament-add-{$this->tournament->id}' class='$classes' $disabledAttr>{$this->tournament->name}</button>";

		$addPopup = new Popup("tournament-add-{$this->tournament->id}");

		if (!$this->inDatabase) {
			$addPopup->content = new TournamentEditForm($this->tournament, true, $this->parentIds, $this->childrenIds);
		}

		return $button . $addPopup->render();
	}

	public function __toString(): string {
		return $this->render();
	}
}