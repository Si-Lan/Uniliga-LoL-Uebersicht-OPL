<?php

namespace App\Domain\Factories;

use Exception;

abstract class AbstractFactory {
	/**
	 * Alle möglichen Spalten aus der Datenbank
	 * @var array<string>
	 */
	protected static array $DB_DATA_KEYS = [];
	/**
	 * Alle Spalten aus der Datenbank, welche nicht null sein dürfen
	 * @var array<string>
	 */
	protected static array $REQUIRED_DB_DATA_KEYS = [];

	/**
	 * @throws Exception
	 */
	protected function normalizeDbData(array $data): array {
		$defaults = array_fill_keys(static::$DB_DATA_KEYS, null);
		$normalizedData = array_merge($defaults, $data);
		foreach (static::$REQUIRED_DB_DATA_KEYS as $key) {
			if (is_null($normalizedData[$key])) {
				throw new Exception("Missing required key '$key'");
			}
		}
		return $normalizedData;
	}
}