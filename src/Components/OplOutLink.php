<?php

namespace App\Components;
use App\Entities\Player;
use App\Entities\Team;
use App\Entities\Tournament;

class OplOutLink {
	private string $oplUrl = '';
	private string $entityId = '';
	public function __construct(
		Tournament|Team|Player $entity
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
	}

	public function render(): string {
		$oplUrl = $this->oplUrl;
		$entityId = $this->entityId;
		ob_start();
		include BASE_PATH.'/resources/components/opl-out-link.php';
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}
}