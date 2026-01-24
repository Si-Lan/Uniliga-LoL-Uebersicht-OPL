<?php

namespace App\Core\Utilities;

use DateTimeImmutable;

class DateTimeHelper {
	public static function getRelativeTimeString(?DateTimeImmutable $dateTime): string {
		if ($dateTime == null) {
			return 'unbekannt';
		}
		$currentTime = new DateTimeImmutable();
		$diff = $currentTime->diff($dateTime, true);

		if ($diff->y > 0) {
			return $diff->y === 1 ? 'vor 1 Jahr' : "vor {$diff->y} Jahren";
		}
		if ($diff->m > 0) {
			return $diff->m === 1 ? 'vor 1 Monat' : "vor {$diff->m} Monaten";
		}
		if ($diff->d >= 7) {
			$weeks = (int)ceil($diff->d / 7);
			return $weeks === 1 ? 'vor 1 Woche' : "vor {$weeks} Wochen";
		}
		if ($diff->d > 0) {
			return $diff->d === 1 ? 'Gestern' : "vor {$diff->d} Tagen";
		}
		if ($diff->h > 0) {
			return $diff->h === 1 ? 'vor 1 Stunde' : "vor {$diff->h} Stunden";
		}
		if ($diff->i > 0) {
			return $diff->i === 1 ? 'vor 1 Minute' : "vor {$diff->i} Minuten";
		}
		if ($diff->s >= 30) {
			return "vor {$diff->s} Sekunden";
		}
		return 'vor wenigen Sekunden';
	}
}