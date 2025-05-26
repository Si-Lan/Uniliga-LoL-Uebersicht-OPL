<?php

namespace App\Core\Utilities;

use App\Domain\Enums\EventFormat;
use App\Domain\Enums\EventType;

trait DataParsingHelpers {

	protected function stringOrNull(mixed $value): ?string {
		return is_null($value) ? null : (string) $value;
	}

	protected function intOrNull(mixed $value): ?int {
		return is_null($value) ? null : (int) $value;
	}

	protected function floatOrNull(mixed $value): ?float {
		return is_null($value) ? null : (float) $value;
	}

	protected function DateTimeImmutableOrNull(mixed $value): ?\DateTimeImmutable {
		return is_null($value) ? null : new \DateTimeImmutable($value);
	}

	protected function EventTypeEnumOrNull(mixed $value): ?EventType {
		return is_null($value) ? null : EventType::tryFrom($value);
	}

	protected function EventFormatEnumOrNull(mixed $value): ?EventFormat {
		return is_null($value) ? null : EventFormat::tryFrom($value);
	}

	/**
	 * Dekodiert einen JSON String oder gibt eingegebenes default Array zurück
	 *
	 * @param string|null $json JSON-String zum dekodieren
	 * @param array|string $default JSON-String oder Array für Standardrückgabe
	 * @return array
	 */
	protected function decodeJsonOrDefault(string|null $json, array|string $default = []): array {
		if (is_null($json) || !json_validate($json)) {
			if (is_array($default)) {
				return $default;
			}
			if (json_validate($default)) {
				return json_decode($default, true);
			} else {
				throw new \InvalidArgumentException("Invalid default JSON provided");
			}
		}
		return json_decode($json, true);
	}

	/**
	 * Dekodiert einen JSON String oder gibt null zurück
	 *
	 * @param string|null $json JSON-String zum dekodieren
	 * @return array|null
	 */
	protected function decodeJsonOrNull(string|null $json): array|null {
		return (is_null($json) || !json_validate($json)) ? null : json_decode($json, true);
	}

}