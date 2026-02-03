<?php

namespace App\UI\Components;
use App\Domain\Entities\Matchup;
use App\Domain\Entities\Player;
use App\Domain\Entities\Team;
use App\Domain\Entities\Tournament;

class OplOutLink {
	private string $oplUrl = '';
	private string $entityId = '';
	public function __construct(
		Tournament|Team|Player|Matchup $entity
	) {
		$this->entityId = $entity->id;
		if ($entity instanceof Tournament) {
			$this->oplUrl = 'https://www.opleague.pro/event/';
		}
		if ($entity instanceof Team) {
			$this->oplUrl = 'https://www.opleague.pro/team/';
		}
		if ($entity instanceof Player) {
			$this->oplUrl = 'https://www.opleague.pro/user/';
		}
		if ($entity instanceof Matchup) {
			$this->oplUrl = 'https://www.opleague.pro/match/';
		}
	}

	public function render(): string {
		$oplUrl = $this->oplUrl;
		$entityId = $this->entityId;
		ob_start();
		include __DIR__.'/opl-out-link.template.php';
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}
}