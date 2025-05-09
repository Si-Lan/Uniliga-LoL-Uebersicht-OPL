<?php

namespace App\Components\Cards;

use App\Entities\Patch;
use App\Entities\Player;
use App\Entities\PlayerInTeamInTournament;
use App\Entities\PlayerSeasonRank;
use App\Entities\RankedSplit;
use App\Repositories\PatchRepository;
use App\Repositories\PlayerInTeamInTournamentRepository;
use App\Repositories\PlayerRepository;
use App\Repositories\PlayerSeasonRankRepository;
use App\Repositories\RankedSplitRepository;
use App\Repositories\TournamentRepository;

include_once BASE_PATH."/src/functions/helper.php";

class SummonerCard {
	private Player $player;
	private ?PlayerInTeamInTournament $playerTT;
	private ?PlayerSeasonRank $playerSeasonRank1;
	private ?PlayerSeasonRank $playerSeasonRank2;
	private ?RankedSplit $currentSplit;
	private Patch $latestPatch;
	private bool $collapsed;
	public function __construct(
		int $playerID,
		int $teamID,
		?int $tournamentID=null,
	) {
		$playerRepo = new PlayerRepository();
		$this->player = $playerRepo->findById($playerID);

		$tournamentRepo = new TournamentRepository();
		$tournament = ($tournamentID != null) ? $tournamentRepo->findById($tournamentID) : null;

		$rankedSplitRepo = new RankedSplitRepository();
		$rankedSplit1 =  ($tournamentID != null) ? $rankedSplitRepo->findFirstSplitForTournament($tournament) : null;
		$rankedSplit2 = ($tournamentID != null) ? $rankedSplitRepo->findNextSplitForTournament($tournament) : null;
		$this->currentSplit = ($tournamentID != null) ? $rankedSplitRepo->findSelectedSplitForTournament($tournament) : null;

		$playerTTRepo = new PlayerInTeamInTournamentRepository();
		$this->playerTT = ($tournamentID != null) ? $playerTTRepo->findByPlayerIdAndTeamIdAndTournamentId($playerID, $teamID, $tournamentID) : null;

		$playerSeasonRankRepo = new PlayerSeasonRankRepository();
		$this->playerSeasonRank1 = ($tournamentID != null) ? $playerSeasonRankRepo->findPlayerSeasonRank($playerID, $rankedSplit1) : null;
		$this->playerSeasonRank2 = ($rankedSplit2 != null && $tournamentID != null) ? $playerSeasonRankRepo->findPlayerSeasonRank($playerID, $rankedSplit2) : null;

		$patchRepo = new PatchRepository();
		$this->latestPatch = $patchRepo->findLatestPatchWithAllData();

		$this->collapsed = ($tournamentID === null) || summonercards_collapsed();
	}

	public function render(): string
	{
		$player = $this->player;
		$playerTT = $this->playerTT;
		$playerRanks = [$this->playerSeasonRank1, $this->playerSeasonRank2];
		$currentSplit = $this->currentSplit;
		$latestPatch = $this->latestPatch;
		$collapsed = $this->collapsed;

		ob_start();
		include BASE_PATH.'/resources/components/cards/summoner-card.php';
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}
}