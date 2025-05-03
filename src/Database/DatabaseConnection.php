<?php

namespace App\Database;

use Exception;
use mysqli;

class DatabaseConnection {
	private static ?DatabaseConnection $instance = null;
	private mysqli $dbcn;

	/**
	 * @throws Exception
	 */
	private function __construct() {
		$this->dbcn = new mysqli($_ENV["DB_HOST"],$_ENV["DB_USER"],$_ENV["DB_PASS"],$_ENV["DB_DATABASE"],$_ENV["DB_PORT"]);
		if ($this->dbcn->connect_errno) {
			throw new Exception("Error: " . $this->dbcn->connect_error);
		}
		$this->dbcn->set_charset("utf8");
	}

	public static function getInstance():DatabaseConnection {
		if (self::$instance === null) {
			self::$instance = new DatabaseConnection();
		}
		return self::$instance;
	}

	public static function getConnection():mysqli {
		return self::getInstance()->dbcn;
	}
}