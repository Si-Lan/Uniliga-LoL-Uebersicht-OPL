<?php

namespace App\Entities;

use DateTimeImmutable;

class UpdateHistory {
	public function __construct(
		public Tournament|Team|Matchup $entity,
		public ?\DateTimeImmutable $lastUpdate,
		public ?\DateTimeImmutable $lastCronUpdate,
	) {}

	public function getLastUpdate(): ?DateTimeImmutable {
		return max($this->lastUpdate, $this->lastCronUpdate);
	}
	public function getLastUserUpdate(): ?DateTimeImmutable {
		return $this->lastUpdate;
	}
	public function getLastUpdateString(): string {
		$lastUpdate = $this->getLastUpdate();
		if ($lastUpdate == null) {
			return 'unbekannt';
		} else {
			$currentTime = new DateTimeImmutable();
			$diff = $currentTime->diff($lastUpdate, true);

			if ($diff->y >= 1) {
				return $diff->y === 1 ? 'letztes Jahr' : "vor {$diff->y} Jahren";
			}
			if ($diff->m >= 1) {
				return $diff->m === 1 ? 'letzten Monat' : "vor {$diff->m} Monaten";
			}
			if ($diff->d >= 7) {
				$weeks = (int) ceil($diff->d / 7);
				return $weeks === 1 ? "letzte Woche" : "vor {$weeks} Wochen";
			}
			if ($diff->d >= 1) {
				return $diff->d === 1 ? 'Gestern' : "vor {$diff->d} Tagen";
			}
			if ($diff->h >= 1) {
				return $diff->h === 1 ? 'vor 1 Stunde' : "vor {$diff->h} Stunden";
			}
			if ($diff->i >= 1) {
				return $diff->i === 1 ? 'vor 1 Minute' : "vor {$diff->i} Minuten";
			}

			if ($diff->s < 30) return 'vor wenigen Sekunden';
			return "vor {$diff->s} Sekunden";
		}
	}
}