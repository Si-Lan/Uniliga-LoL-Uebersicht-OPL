<?php

namespace App\Domain\Factories;

use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Entities\Matchup;
use App\Domain\Entities\TeamInTournament;
use App\Domain\Entities\Tournament;
use App\Domain\Factories\AbstractFactory;
use App\Domain\Repositories\TeamInTournamentRepository;
use App\Domain\Repositories\TournamentRepository;

class MatchupFactory extends AbstractFactory {
	use DataParsingHelpers;

	protected static array $DB_DATA_KEYS = [
		'OPL_ID',
		'OPL_ID_tournament',
		'OPL_ID_team1',
		'OPL_ID_team2',
		'team1Score',
		'team2Score',
		'plannedDate',
		'playday',
		'bestOf',
		'played',
		'winner',
		'loser',
		'draw',
		'def_win',
		'has_custom_score',
		'has_custom_games'
	];
	protected static array $REQUIRED_DB_DATA_KEYS = [
		'OPL_ID',
		'OPL_ID_tournament',
		'played'
	];

	public function __construct(
		private ?TournamentRepository $tournamentRepo = null,
		private ?TeamInTournamentRepository $teamInTournamentRepo = null
	) {
		if ($this->tournamentRepo === null) $this->tournamentRepo = new TournamentRepository();
		if ($this->teamInTournamentRepo === null) $this->teamInTournamentRepo = new TeamInTournamentRepository();
	}

	public function createFromDbData(
		array $data,
		?Tournament $tournamentStage = null,
		?TeamInTournament $team1 = null,
		?TeamInTournament $team2 = null
	): Matchup {
		$data = $this->normalizeDbData($data);
		if ($tournamentStage === null) {
			$tournamentStage = $this->tournamentRepo->findById($data['OPL_ID_tournament']);
		}
		if ($team1 === null && $data['OPL_ID_team1'] !== null) {
			$team1 = $this->teamInTournamentRepo->findByTeamIdAndTournament($data['OPL_ID_team1'],$tournamentStage->rootTournament);
		}
		if ($team2 === null && $data['OPL_ID_team2'] !== null) {
			$team2 = $this->teamInTournamentRepo->findByTeamIdAndTournament($data['OPL_ID_team2'],$tournamentStage->rootTournament);
		}
		return new Matchup(
			id: (int) $data['OPL_ID'],
			tournamentStage: $tournamentStage,
			team1: $team1,
			team2: $team2,
			team1Score: $this->stringOrNull($data['team1Score']),
			team2Score: $this->stringOrNull($data['team2Score']),
			plannedDate: $this->DateTimeImmutableOrNull($data['plannedDate']),
			playday: $this->intOrNull($data['playday']),
			bestOf: $this->intOrNull($data['bestOf']),
			played: (bool) $data['played'],
			winnerId: $this->intOrNull($data['winner']),
			loserId: $this->intOrNull($data['loser']),
			draw: (bool) $data['draw'] ?? false,
			defWin: (bool) $data['def_win'] ?? false,
			hasCustomScore: (bool) $data['has_custom_score'] ?? false,
			hasCustomGames: (bool) $data['has_custom_games'] ?? false
		);
	}

	public function mapEntityToDbData(Matchup $matchup): array {
		return [
			"OPL_ID" => $matchup->id,
			"OPL_ID_tournament" => $matchup->tournamentStage->id,
			"OPL_ID_team1" => $matchup->team1?->team->id,
			"OPL_ID_team2" => $matchup->team2?->team->id,
			"team1Score" => $matchup->team1Score,
			"team2Score" => $matchup->team2Score,
			"plannedDate" => $matchup->plannedDate?->format("Y-m-d H:i:s") ?? null,
			"playday" => $matchup->playday,
			"bestOf" => $matchup->bestOf,
			"played" => $this->intOrNull($matchup->played),
			"winner" => $matchup->winnerId,
			"loser" => $matchup->loserId,
			"draw" => $this->intOrNull($matchup->draw),
			"def_win" => $this->intOrNull($matchup->defWin),
			"has_custom_score" => (int) $matchup->hasCustomScore,
			"has_custom_games" => (int) $matchup->hasCustomGames
		];
	}

	public function createFromOplData(array $oplData): Matchup {
		$teamIds = array_keys($oplData['teams']);
		$team1 = null;
		$team2 = null;
		if (count($teamIds) > 0) $team1 = $oplData['teams'][$teamIds[0]]['ID'];
		if (count($teamIds) > 1) $team2 = $oplData['teams'][$teamIds[1]]['ID'];

		$entityData = [
			"OPL_ID" => $oplData['ID'],
			"OPL_ID_tournament" => $oplData['tournament']['ID'],
			"OPL_ID_team1" => $team1,
			"OPL_ID_team2" => $team2,
			"plannedDate" => $oplData['to_be_played_on'],
			"playday" => $oplData['playday'],
			"bestOf" => $oplData['best_of'],
			"played" => false
		];

		return $this->createFromDbData($entityData);
	}

	public function updateFromOplData(Matchup $matchup, array $oplData): Matchup {
		$newMatchup = $this->createFromOplData($oplData);

		if ($matchup->id !== $newMatchup->id) {
			throw new \Exception("Matchup ID mismatch");
		}

		if ($matchup->team1?->team?->id !== $newMatchup->team1?->team?->id) {
			$matchup->team1 = $newMatchup->team1;
		}
		if ($matchup->team2?->team?->id !== $newMatchup->team2?->team?->id) {
			$matchup->team2 = $newMatchup->team2;
		}
		if ($matchup->plannedDate !== $newMatchup->plannedDate) {
			$matchup->plannedDate = $newMatchup->plannedDate;
		}
		if ($matchup->playday !== $newMatchup->playday) {
			$matchup->playday = $newMatchup->playday;
		}
		if ($matchup->bestOf !== $newMatchup->bestOf) {
			$matchup->bestOf = $newMatchup->bestOf;
		}

		return $matchup;
	}

}