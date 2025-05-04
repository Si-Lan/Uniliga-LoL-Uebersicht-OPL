<?php

namespace App\Utilities;

trait NullableCastTrait {

	protected function nullableString(mixed $value): ?string {
		return is_null($value) ? null : (string) $value;
	}

	protected function nullableInt(mixed $value): ?int {
		return is_null($value) ? null : (int) $value;
	}

	protected function nullableFloat(mixed $value): ?float {
		return is_null($value) ? null : (float) $value;
	}

	protected function nullableDateTime(mixed $value): ?\DateTimeImmutable {
		return is_null($value) ? null : new \DateTimeImmutable($value);
	}

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

	protected function decodeJsonOrNull(string|null $json): array|null {
		return (is_null($json) || !json_validate($json)) ? null : json_decode($json, true);
	}

}