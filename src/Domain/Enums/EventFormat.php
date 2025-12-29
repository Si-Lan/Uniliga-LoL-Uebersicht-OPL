<?php

namespace App\Domain\Enums;

enum EventFormat: string {
	case ROUND_ROBIN = 'round-robin';
	case SINGLE_ELIMINATION = 'single-elimination';
	case DOUBLE_ELIMINATION = 'double-elimination';
	case SWISS = 'swiss';

	public static function fromString(string $format): EventFormat|null {
		$format = strtolower($format);
		$format = str_replace(' ', '-', $format);
		return match ($format) {
			'round-robin' => self::ROUND_ROBIN,
			'single-elimination' => self::SINGLE_ELIMINATION,
			'double-elimination' => self::DOUBLE_ELIMINATION,
			'swiss' => self::SWISS,
			default => null
		};
	}
}
