<?php

namespace App\Domain\Factories;

use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Entities\Game;
use App\Domain\Entities\Matchup;
use App\Domain\Entities\MatchupChangeSuggestions;
use App\Domain\Enums\SuggestionStatus;
use App\Domain\Repositories\GameRepository;
use App\Domain\Repositories\MatchupRepository;

class MatchupChangeSuggestionFactory extends AbstractFactory {
	use DataParsingHelpers;

	protected static array $DB_DATA_KEYS = [
		'id',
		'OPL_ID_matchup',
		'customTeam1Score',
		'customTeam2Score',
		'addedGames',
		'removedGames',
		'status',
		'created_at',
		'finished_at'
	];
	protected static array $REQUIRED_DB_DATA_KEYS = [
		'id',
		'OPL_ID_matchup',
		'status'
	];

	public function __construct(
		private ?MatchupRepository $matchupRepo = null,
		private ?GameRepository $gameRepo = null
	) {
		if ($this->matchupRepo === null) $this->matchupRepo = new MatchupRepository();
		if ($this->gameRepo === null) $this->gameRepo = new GameRepository();
	}

	public function createFromDbData(
		array $data,
		?Matchup $matchup = null
	): MatchupChangeSuggestions	{
		$data = $this->normalizeDbData($data);
		if ($matchup === null) {
			$matchup = $this->matchupRepo->findById($data['OPL_ID_matchup']);
		}

		$addedGameIds = $this->decodeJsonOrDefault($data['addedGames']);
		$addedGames = [];
		foreach ($addedGameIds as $addedGameId) {
			$addedGames[] = $this->gameRepo->findById($addedGameId);
		}
		$removedGameIds = $this->decodeJsonOrDefault($data['removedGames']);
		$removedGames = [];
		foreach ($removedGameIds as $removedGameId) {
			$removedGames[] = $this->gameRepo->findById($removedGameId);
		}

		return new MatchupChangeSuggestions(
			id: (int) $data['id'],
			matchup: $matchup,
			customTeam1Score: $this->stringOrNull($data['customTeam1Score']),
			customTeam2Score: $this->stringOrNull($data['customTeam2Score']),
			addedGames: $addedGames,
			removedGames: $removedGames,
			status: SuggestionStatus::tryFrom($data['status']??''),
			createdAt: $this->DateTimeImmutableOrNull($data['createdAt']??''),
			finishedAt: $this->DateTimeImmutableOrNull($data['finished_at']??'')
		);
	}

	public function mapEntityToDbData(MatchupChangeSuggestions $matchupChangeSuggestion): array {
		$addedGameIds = array_map(fn(Game $game) => $game->id, $matchupChangeSuggestion->addedGames ?? []);
		$removedGameIds = array_map(fn(Game $game) => $game->id, $matchupChangeSuggestion->removedGames ?? []);
		return [
			"id" => $matchupChangeSuggestion->id,
			"OPL_ID_matchup" => $matchupChangeSuggestion->matchup->id,
			"customTeam1Score" => $matchupChangeSuggestion->customTeam1Score,
			"customTeam2Score" => $matchupChangeSuggestion->customTeam2Score,
			"addedGames" => json_encode($addedGameIds),
			"removedGames" => json_encode($removedGameIds),
			"status" => $matchupChangeSuggestion->status->value,
			"finished_at" => $matchupChangeSuggestion->finishedAt?->format("Y-m-d H:i:s"),
		];
	}

	public function createNew(
		Matchup $matchup,
		?string $customTeam1Score = null,
		?string $customTeam2Score = null,
		?array $addedGames = [],
		?array $removedGames = []
	): MatchupChangeSuggestions {
		return new MatchupChangeSuggestions(
			id: null,
			matchup: $matchup,
			customTeam1Score: $customTeam1Score,
			customTeam2Score: $customTeam2Score,
			addedGames: $addedGames,
			removedGames: $removedGames,
			status: SuggestionStatus::PENDING,
			createdAt: null,
			finishedAt: null,
		);
	}
}