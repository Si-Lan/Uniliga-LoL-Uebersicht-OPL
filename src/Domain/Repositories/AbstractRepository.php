<?php

namespace App\Domain\Repositories;

use App\Core\DatabaseConnection;

abstract class AbstractRepository {
	protected \mysqli $dbcn;
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

	/**
	 * Alle Spalten, die bei Updates via OPL-API aktualisiert werden sollen
	 * @var array<string>
	 */
	protected static array $OPL_DATA_KEYS = [];

	public function __construct() {
		$this->dbcn = DatabaseConnection::getConnection();
	}

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

	protected function filterKeysFromOpl(array $data): array {
		return array_filter($data, fn($key) => in_array($key, static::$OPL_DATA_KEYS), ARRAY_FILTER_USE_KEY);
	}

	public function dataHasRequiredFields(array $data): bool {
		foreach (static::$REQUIRED_DATA_KEYS as $key) {
			if (!array_key_exists($key, $data)) {
				return false;
			}
		}
		return true;
	}
	public function dataHasAllFields(array $data): bool {
		foreach (static::$ALL_DATA_KEYS as $key) {
			if (!array_key_exists($key, $data)) {
				return false;
			}
		}
		return true;
	}
}