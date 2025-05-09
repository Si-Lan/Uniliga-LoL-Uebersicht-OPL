<?php

namespace App\Utilities;

use App\Entities\PlayerInTeamInTournament;

class EntitySorter {
	/**
	 * @param array<PlayerInTeamInTournament> $players
	 * @return array<PlayerInTeamInTournament>
	 */
	public static function sortPlayersByAllRoles(array $players): array {
		usort($players, function (PlayerInTeamInTournament $a,PlayerInTeamInTournament $b) {
			return $b->getTotalRoles() <=> $a->getTotalRoles();
		});
		return $players;
	}
}