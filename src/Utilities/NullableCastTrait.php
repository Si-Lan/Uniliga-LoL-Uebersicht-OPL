<?php

namespace App\Utilities;

trait NullableCastTrait {

	protected function nullableString(mixed $value): ?string {
		return is_null($value) ? null : (string) $value;
	}

	protected function nullableInt(mixed $value): ?int {
		return is_null($value) ? null : (int) $value;
	}

}