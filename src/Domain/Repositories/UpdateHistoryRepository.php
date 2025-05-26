<?php

namespace App\Domain\Repositories;

use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Entities\Matchup;
use App\Domain\Entities\TeamInTournament;
use App\Domain\Entities\Tournament;
use App\Domain\Entities\UpdateHistory;
use App\Domain\Enums\EventType;

class UpdateHistoryRepository extends AbstractRepository {
	use DataParsingHelpers;
	public function mapToEntity(array $data, Tournament|TeamInTournament|Matchup $entity): UpdateHistory {
		return new UpdateHistory(
			entity: $entity,
			lastUpdate: $this->DateTimeImmutableOrNull($data['last_update']),
			lastCronUpdate: $this->DateTimeImmutableOrNull($data['last_cron_update']),
		);
	}

	public function findByTournamentStage(Tournament $tournamentStage): ?UpdateHistory {
		if ($tournamentStage->eventType === EventType::TOURNAMENT) return null;
		$query = 'SELECT t.OPL_ID, uu.last_update, c.last_update as last_cron_update FROM tournaments t LEFT JOIN updates_user_group uu ON t.OPL_ID = uu.OPL_ID_group LEFT JOIN updates_cron c ON c.OPL_ID_tournament = t.OPL_ID_top_parent WHERE t.OPL_ID = ?';
		$result = $this->dbcn->execute_query($query, [$tournamentStage->id]);
		$data = $result->fetch_assoc();

		return $data ? $this->mapToEntity($data, $tournamentStage) : null;
	}
	public function findByTeamInTournament(TeamInTournament $team): ?UpdateHistory {
		$query = 'SELECT t.OPL_ID, uu.last_update, c.last_update as last_cron_update FROM teams t LEFT JOIN updates_user_team uu ON t.OPL_ID = uu.OPL_ID_team LEFT JOIN updates_cron c ON c.OPL_ID_tournament = ? WHERE t.OPL_ID = ?';
		$result = $this->dbcn->execute_query($query, [$team->tournament->id, $team->team->id]);
		$data = $result->fetch_assoc();

		return $data ? $this->mapToEntity($data, $team) : null;
	}
	public function findByMatchup(Matchup $matchup): ?UpdateHistory {
		$query = 'SELECT m.OPL_ID, uu.last_update, c.last_update as last_cron_update FROM matchups m LEFT JOIN updates_user_matchup uu ON m.OPL_ID = uu.OPL_ID_matchup LEFT JOIN updates_cron c ON c.OPL_ID_tournament = ? WHERE m.OPL_ID = ?';
		$result = $this->dbcn->execute_query($query, [$matchup->tournamentStage->rootTournament->id, $matchup->id]);
		$data = $result->fetch_assoc();

		return $data ? $this->mapToEntity($data, $matchup) : null;
	}
}