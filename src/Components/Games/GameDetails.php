<?php

namespace App\Components\Games;

use App\Entities\Game;
use App\Entities\GameInMatch;
use App\Entities\LolGame\GamePlayerData;
use App\Entities\Patch;
use App\Entities\PlayerInTeamInTournament;
use App\Entities\PlayerSeasonRank;
use App\Entities\RankedSplit;
use App\Entities\Team;
use App\Repositories\GameInMatchRepository;
use App\Repositories\PatchRepository;
use App\Repositories\PlayerInTeamInTournamentRepository;
use App\Repositories\PlayerSeasonRankRepository;
use App\Repositories\RankedSplitRepository;

class GameDetails {
	private GameInMatch $gameInMatch;
	/**
	 * @var array<string,PlayerInTeamInTournament>
	 */
	private array $indexedPlayersByPuuid = [];
	/**
	 * @var array<string,array<PlayerSeasonRank>>
	 */
	private array $indexedPlayerSeasonRanksByPuuid = [];
	private ?RankedSplit $currentSplit;

	private Patch $patch;
	public function __construct(
		private Game $game,
		private ?Team $currentTeam = null,
		private ?PlayerInTeamInTournamentRepository $playerInTeamInTournamentRepo = null,
		private ?PlayerSeasonRankRepository $playerSeasonRankRepo = null,
	) {
		$gameInMatchRepo = new GameInMatchRepository();
		$patchRepo = new PatchRepository();
		$rankedSplitRepo = new RankedSplitRepository();
		if (is_null($this->playerInTeamInTournamentRepo)) {
			$this->playerInTeamInTournamentRepo = new PlayerInTeamInTournamentRepository();
		}
		if (is_null($this->playerSeasonRankRepo)) {
			$this->playerSeasonRankRepo = new PlayerSeasonRankRepository();
		}

		$this->gameInMatch = $gameInMatchRepo->findByGame($game);

		$this->patch = $patchRepo->findLatestPatchByPatchString($game->gameData->gameVersion);

		$rankedSplit1 = $this->gameInMatch->matchup->tournamentStage->rootTournament->rankedSplit;
		$rankedSplit2 = $rankedSplitRepo->findNextSplitForTournament($this->gameInMatch->matchup->tournamentStage->rootTournament);
		$this->currentSplit = $this->gameInMatch->matchup->tournamentStage->rootTournament->userSelectedRankedSplit;


		$playersInTeams = $this->playerInTeamInTournamentRepo->findAllByTeamAndTournament($this->gameInMatch->blueTeam->team,$this->gameInMatch->matchup->tournamentStage->rootTournament);
		$playersInTeams = array_merge($playersInTeams, $this->playerInTeamInTournamentRepo->findAllByTeamAndTournament($this->gameInMatch->redTeam->team,$this->gameInMatch->matchup->tournamentStage->rootTournament));

		foreach ($playersInTeams as $playerInTeam) {
			$this->indexedPlayerSeasonRanksByPuuid[$playerInTeam->player->puuid] = [
				$this->playerSeasonRankRepo->findPlayerSeasonRank($playerInTeam->player,$rankedSplit1),
				$this->playerSeasonRankRepo->findPlayerSeasonRank($playerInTeam->player,$rankedSplit2),
			];
			$this->indexedPlayersByPuuid[$playerInTeam->player->puuid] = $playerInTeam;
		}
	}

	private function findPlayersGameNameAndTag(GamePlayerData $gamePlayer):array {
		if (($this->indexedPlayersByPuuid[$gamePlayer->puuid]->player->riotIdName ??'') !== '') {
			return [$this->indexedPlayersByPuuid[$gamePlayer->puuid]->player->riotIdName,$this->indexedPlayersByPuuid[$gamePlayer->puuid]->player->riotIdTag];
		}
		if ($gamePlayer->riotIdName != '') {
			return [$gamePlayer->riotIdName,$gamePlayer->riotIdTag];
		}
		return [$gamePlayer->summonerName,null];
	}
	private function findPlayersSeasonRanks(GamePlayerData $gamePlayer): array {
		return $this->indexedPlayerSeasonRanksByPuuid[$gamePlayer->puuid] ?? [];
	}

	public function render(): string {
		$gameData = $this->game->gameData;
		$currentTeam = $this->currentTeam;
		$gameInMatch = $this->gameInMatch;
		$patch = $this->patch;
		$currentSplit = $this->currentSplit;
		ob_start();
		include __DIR__.'/game-details.template.php';
		return ob_get_clean();
	}

	public function __toString(): string {
		return $this->render();
	}
}