<?php

namespace App\Utilities;

use App\Entities\Matchup;
use App\Entities\PlayerInTeamInTournament;
use App\Entities\TeamInTournamentStage;
use App\Enums\EventType;

class EntitySorter {
	/**
	 * @param array<TeamInTournamentStage> $tournamentStages
	 * @return array<TeamInTournamentStage>
	 */
	public static function sortTeamInTournamentStages(array $teamInTournamentStages): array {
		usort($teamInTournamentStages, function (TeamInTournamentStage $a, TeamInTournamentStage $b) {
			$prioMap = [
				EventType::WILDCARD->value => 1,
				EventType::LEAGUE->value => 2,
				EventType::GROUP->value => 2,
				EventType::PLAYOFFS->value => 3,
			];

			$compare = $prioMap[$a->tournamentStage->eventType->value] <=> $prioMap[$b->tournamentStage->eventType->value];
			if ($compare !== 0) {
				return $compare;
			}

			if ($a->tournamentStage->eventType == EventType::WILDCARD || $a->tournamentStage->eventType == EventType::PLAYOFFS) {
				return $a->tournamentStage->number <=> $b->tournamentStage->number;
			}

			if ($a->tournamentStage->eventType === EventType::GROUP) {
				$aComparer = $a->tournamentStage->directParentTournament->number."-".$a->tournamentStage->number;
			} else {
				$aComparer = (string) $a->tournamentStage->number;
			}
			if ($b->tournamentStage->eventType === EventType::GROUP) {
				$bComparer = $b->tournamentStage->directParentTournament->number."-".$b->tournamentStage->number;
			} else {
				$bComparer = (string) $b->tournamentStage->number;
			}

			return $aComparer <=> $bComparer;

		});
		return $teamInTournamentStages;
	}
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

	/**
	 * @param array<Matchup> $matchups
	 * @return array<Matchup>
	 */
	public static function sortMatchupsByPlayday(array $matchups): array {
		usort($matchups, function (Matchup $a,Matchup $b) {
			return $a->playday <=> $b->playday;
		});
		return $matchups;
	}
	/**
	 * @param array<Matchup> $matchups
	 * @return array<Matchup>
	 */
	public static function sortMatchupsByPlannedDate(array $matchups): array {
		usort($matchups, function (Matchup $a,Matchup $b) {
			if ($a->plannedDate === null && $b->plannedDate === null) return 0;
			if ($a->plannedDate === null) return 1;
			if ($b->plannedDate === null) return -1;
			return $a->plannedDate <=> $b->plannedDate;
		});
		return $matchups;
	}

	/**
	 * @param array<Matchup> $matchups
	 * @return array<int,array<Matchup>>
	 */
	public static function sortAndGroupMatchupsByPlayday(array $matchups): array {
		$matchups = self::sortMatchupsByPlayday($matchups);
		$matchupsGrouped = [];
		foreach ($matchups as $match) {
			$matchupsGrouped[$match->playday][] = $match;
		}
		return $matchupsGrouped;
	}
	/**
	 * @param array<Matchup> $matchups
	 * @return array<int,array<Matchup>>
	 */
	public static function sortAndGroupMatchupsByPlannedDate(array $matchups): array {
		$matchups = self::sortMatchupsByPlannedDate($matchups);
		$matchups = array_filter($matchups, fn(Matchup $matchup) => $matchup->plannedDate !== null);
		$matchupsGrouped = [];
		foreach ($matchups as $match) {
			$matchupsGrouped[$match->plannedDate->format('Y-m-d H')][] = $match;
		}
		return $matchupsGrouped;
	}
}