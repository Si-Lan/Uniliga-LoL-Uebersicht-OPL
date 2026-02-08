<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\MatchupChangeSuggestion;
use App\Domain\Enums\SaveResult;
use App\Domain\Enums\SuggestionStatus;
use App\Domain\Factories\MatchupChangeSuggestionFactory;
use App\Domain\Repositories\AbstractRepository;
use App\Domain\ValueObjects\RepositorySaveResult;

class MatchupChangeSuggestionRepository extends AbstractRepository {
	private MatchupChangeSuggestionFactory $factory;

	public function __construct() {
		parent::__construct();
		$this->factory = new MatchupChangeSuggestionFactory();
	}

	public function findById(int $id): ?MatchupChangeSuggestion {
		$query = "SELECT * FROM matchup_change_suggestions WHERE id = ?";
		$result = $this->dbcn->execute_query($query,[$id]);
		$data = $result->fetch_assoc();

		return $data ? $this->factory->createFromDbData($data) : null;
	}

	/**
	 * @param int $matchupId
	 * @return array<MatchupChangeSuggestion>
	 */
	public function findAllByMatchupId(int $matchupId): array {
		$query = "SELECT * FROM matchup_change_suggestions WHERE OPL_ID_matchup = ?";
		$result = $this->dbcn->execute_query($query,[$matchupId]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$suggestions = [];
		foreach ($data as $suggestionData) {
			$suggestions[] = $this->factory->createFromDbData($suggestionData);
		}
		return $suggestions;
	}

	/**
	 * @param SuggestionStatus $status
	 * @return array<MatchupChangeSuggestion>
	 */
	public function findAllByStatus(SuggestionStatus $status): array {
		$query = "SELECT * FROM matchup_change_suggestions WHERE status = ?";
		$result = $this->dbcn->execute_query($query,[$status->value]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$suggestions = [];
		foreach ($data as $suggestionData) {
			$suggestions[] = $this->factory->createFromDbData($suggestionData);
		}
		return $suggestions;
	}

	public function findAllByMatchupIdAndStatus(int $matchupId, SuggestionStatus $status): array {
		$query = "SELECT * FROM matchup_change_suggestions WHERE OPL_ID_matchup = ? AND status = ?";
		$result = $this->dbcn->execute_query($query,[$matchupId, $status->value]);
		$data = $result->fetch_all(MYSQLI_ASSOC);

		$suggestions = [];
		foreach ($data as $suggestionData) {
			$suggestions[] = $this->factory->createFromDbData($suggestionData);
		}
		return $suggestions;
	}

	public function suggestionExists(int $suggestionId): bool {
		$query = "SELECT id FROM matchup_change_suggestions WHERE id = ?";
		$result = $this->dbcn->execute_query($query,[$suggestionId]);
		return $result->num_rows > 0;
	}

	/* ----- */

	/**
	 * @throws \Exception
	 */
	private function insert(MatchupChangeSuggestion $suggestion): RepositorySaveResult {
		$data = $this->factory->mapEntityToDbData($suggestion);
		if ($data['id'] !== null) {
			throw new \Exception("Trying to insert a suggestion with an existing id: ".var_export($suggestion, true));
		}
		$columns = implode(",", array_keys($data));
		$placeholders = implode(",", array_fill(0, count($data), "?"));
		$values = array_values($data);

		/** @noinspection SqlInsertValues */
		$query = "INSERT INTO matchup_change_suggestions ($columns) VALUES ($placeholders)";
		$this->dbcn->execute_query($query, $values);
		$id = $this->dbcn->insert_id;
		$suggestion->id = $id;
		return new RepositorySaveResult(SaveResult::INSERTED, entity: $suggestion);
	}

	/**
	 * @throws \Exception
	 */
	private function update(MatchupChangeSuggestion $suggestion): RepositorySaveResult {
		if ($suggestion->id === null) {
			throw new \Exception("Trying to update a suggestion without an id: ".var_export($suggestion, true));
		}
		$existingSuggestion = $this->findById($suggestion->id);

		$dataNew = $this->factory->mapEntityToDbData($suggestion);
		$dataOld = $this->factory->mapEntityToDbData($existingSuggestion);
		$dataChanged = array_diff_assoc($dataNew, $dataOld);
		$dataPrevious = array_diff_assoc($dataOld, $dataNew);

		if (count($dataChanged) === 0) {
			return new RepositorySaveResult(SaveResult::NOT_CHANGED);
		}

		$set = implode(",", array_map(fn($key) => "$key = ?", array_keys($dataChanged)));
		$values = array_values($dataChanged);

		$query = "UPDATE matchup_change_suggestions SET $set WHERE id = ?";
		$this->dbcn->execute_query($query, [...$values, $suggestion->id]);

		return new RepositorySaveResult(SaveResult::UPDATED, $dataChanged, $dataPrevious);
	}

	public function save(MatchupChangeSuggestion $suggestion): RepositorySaveResult {
		try {
			if ($suggestion->id && $this->suggestionExists($suggestion->id)) {
				$saveResult = $this->update($suggestion);
			} elseif ($suggestion->id === null) {
				$saveResult = $this->insert($suggestion);
				$suggestion->id = $saveResult->entity->id;
			} else {
				throw new \Exception("Trying to save a suggestion with an invalid id: ".var_export($suggestion, true));
			}
		} catch (\Throwable $e) {
			$this->logger->error("Fehler beim Speicher von MatchupChangeSuggestion: ".$e->getMessage()." in ".$e->getFile()." on Line ".$e->getLine()."\n".$e->getTraceAsString());
			return new RepositorySaveResult(SaveResult::FAILED);
		}
		$saveResult->entity = $this->findById($suggestion->id);
		return $saveResult;
	}
}