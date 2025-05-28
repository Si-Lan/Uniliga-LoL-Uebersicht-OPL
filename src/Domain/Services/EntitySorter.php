<?php

namespace App\Domain\Services;

use App\Domain\Entities\Matchup;
use App\Domain\Entities\Player;
use App\Domain\Entities\PlayerInTeamInTournament;
use App\Domain\Entities\Team;
use App\Domain\Entities\TeamInTournament;
use App\Domain\Entities\TeamInTournamentStage;
use App\Domain\Entities\Tournament;
use App\Domain\Enums\EventType;

class EntitySorter {
	/**
	 * @param array<Tournament> $tournaments
	 * @return array<Tournament>
	 */
	public static function sortTournamentsByStartDate(array $tournaments): array {
		usort($tournaments, function(Tournament $a, Tournament $b) {
			return $b->dateStart <=> $a->dateStart;
		});
		return $tournaments;
	}
	/**
	 * @param array<TeamInTournament> $teamInTournaments
	 * @return array<TeamInTournament>
	 */
	public static function sortTeamInTournamentsByStartDate(array $teamInTournaments): array {
		usort($teamInTournaments, function(TeamInTournament $a, TeamInTournament $b) {
			return $b->tournament->dateStart <=> $a->tournament->dateStart;
		});
		return $teamInTournaments;
	}

	/**
	 * @param array<TeamInTournamentStage> $teamInTournamentStages
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
	public static function removeDuplicateTeamsInTournamentStages(array $teamInTournamentStages): array {
		$teamInTournamentStages = EntitySorter::sortTeamInTournamentStages($teamInTournamentStages);

		$foundTeams = [];
		foreach ($teamInTournamentStages as $index=>$teamInTournamentStage) {
			if (isset($foundTeams[$teamInTournamentStage->team->id])) {
				unset($teamInTournamentStages[$index]);
			}
			$foundTeams[$teamInTournamentStage->team->id] = true;
		}

		return $teamInTournamentStages;
	}

	/**
	 * @param array<PlayerInTeamInTournament> $players
	 * @return array<PlayerInTeamInTournament>
	 */
	public static function sortPlayersByAllRoles(array $players): array {
		usort($players, function (PlayerInTeamInTournament $a,PlayerInTeamInTournament $b) {
			return $b->stats->getTotalRoles() <=> $a->stats->getTotalRoles();
		});
		return $players;
	}
	/**
	 * @param array<PlayerInTeamInTournament> $players
	 * @return array<PlayerInTeamInTournament>
	 */
	public static function sortPlayersByMostPlayedRoles(array $players): array {
		usort($players, function (PlayerInTeamInTournament $a,PlayerInTeamInTournament $b) {
			$prioMap = [
				"top" => 6,
				"jungle" => 5,
				"middle" => 4,
				"bottom" => 3,
				"utility" => 2,
				"none" => 1
			];
			return $prioMap[self::highestRole($b->stats->roles)] <=> $prioMap[self::highestRole($a->stats->roles)];
		});
		return $players;
	}
	private static function highestRole(array $array) {
		arsort($array);
		if ($array[array_key_first($array)] == 0) {
			return "none";
		}
		return array_key_first($array);
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
	 * @return Matchup
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
	 * @return Matchup
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

	/**
	 * @param array<Player|Team> $entities
	 * @param string $matchString
	 * @return array<Player|Team>
	 */
	public static function sortByNameMatchingString(array $entities, string $matchString): array {
		$remaining_entities = $entities;

		$starting_hits = [];
		foreach ($remaining_entities as $index=>$result) {
			if (str_starts_with(strtolower($result->name), strtolower($matchString))
				|| ($result instanceof Player && str_starts_with(strtolower($result->riotIdName??''), strtolower($matchString)))) {
				$starting_hits[] = $result;
				unset($remaining_entities[$index]);
			}
		}

		$contain_hits = [];
		foreach ($remaining_entities as $index=>$result) {
			if (str_contains(strtolower($result->name), strtolower($matchString))
				|| ($result instanceof Player && str_contains(strtolower($result->riotIdName??''), strtolower($matchString)))){
				$contain_hits[] = $result;
				unset($remaining_entities[$index]);
			}
		}

		$compare_searchresults = function($a,$b) use ($matchString) {
			if ($a instanceof Player) {
				$a_compare = min(levenshtein($matchString,$a->name??''), levenshtein($matchString,$a->riotIdName??''));
			} else {
				$a_compare = levenshtein($matchString,$a->name??"");
			}
			if ($b instanceof Player) {
				$b_compare = min(levenshtein($matchString,$b->name??''), levenshtein($matchString,$b->riotIdName??''));
			} else {
				$b_compare = levenshtein($matchString,$b->name??'');
			}
			return $a_compare <=> $b_compare;
		};

		usort($starting_hits, $compare_searchresults);
		usort($contain_hits, $compare_searchresults);
		usort($remaining_entities, $compare_searchresults);

		return array_merge($starting_hits, $contain_hits, $remaining_entities);
	}
}