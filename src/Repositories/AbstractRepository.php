<?php

namespace App\Repositories;

abstract class AbstractRepository {
	/**
	 * Alle möglichen Spalten aus der Datenbank
	 * @var array<string>
	 */
	protected static array $ALL_DATA_KEYS = [];
	/**
	 * Alle Spalten aus der Datenbank welche nicht null sein dürfen
	 * @var array<string>
	 */
	protected static array $REQUIRED_DATA_KEYS = [];

	protected function normalizeData(array $data): array {
		$defaults = array_fill_keys(static::$ALL_DATA_KEYS, null);
		$normalized = array_merge($defaults, $data);

		foreach (static::$REQUIRED_DATA_KEYS as $key) {
			if (is_null($normalized[$key])) {
				throw new \InvalidArgumentException("Missing required key '$key'");
			}
		}
		return $normalized;
	}

	public function dataHasRequiredFields(array $data): bool {
		foreach (static::$REQUIRED_DATA_KEYS as $key) {
			if (!array_key_exists($key, $data)) {
				return false;
			}
		}
		return true;
	}
}