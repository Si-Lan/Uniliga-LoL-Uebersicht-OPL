<?php

namespace App\Components;

use App\Repositories\PatchRepository;
use App\Repositories\PlayerSeasonRankRepository;
use App\Repositories\TournamentRepository;
use App\Repositories\PlayerInTeamInTournamentRepository;
use App\Repositories\RankedSplitRepository;
use App\Entities\PlayerInTeamInTournament;
use App\Entities\PlayerSeasonRank;
use App\Entities\RankedSplit;
use App\Entities\Patch;

include_once dirname(__DIR__)."/functions/helper.php";

class SummonerCard {
	private PlayerInTeamInTournament $playerTT;
	private ?PlayerSeasonRank $playerSeasonRank1;
	private ?PlayerSeasonRank $playerSeasonRank2;
	private RankedSplit $currentSplit;
	private Patch $latestPatch;
	private bool $collapsed;
	public function __construct(
		\mysqli $dbcn,
		int $playerID,
		int $tournamentID,
		int $teamID,
	) {
		$tournamentRepo = new TournamentRepository();
		$tournament = $tournamentRepo->findById($tournamentID);

		$rankedSplitRepo = new RankedSplitRepository();
		$rankedSplit1 = $rankedSplitRepo->findFirstSplitForTournament($tournament);
		$rankedSplit2 = $rankedSplitRepo->findNextSplitForTournament($tournament);
		$this->currentSplit = $rankedSplitRepo->findSelectedSplitForTournament($tournament);

		$playerTTRepo = new PlayerInTeamInTournamentRepository();
		$this->playerTT = $playerTTRepo->findByPlayerIdAndTeamIdAndTournamentId($playerID, $teamID, $tournamentID);

		$playerSeasonRankRepo = new PlayerSeasonRankRepository();
		$this->playerSeasonRank1 = $playerSeasonRankRepo->findByPlayerIdAndRankedSplit($playerID, $rankedSplit1);
		$this->playerSeasonRank2 = ($rankedSplit2 != null) ? $playerSeasonRankRepo->findByPlayerIdAndRankedSplit($playerID, $rankedSplit2) : null;

		$patchRepo = new PatchRepository();
		$this->latestPatch = $patchRepo->findLatestPatchWithAllData();

		$this->collapsed = summonercards_collapsed();
	}

	public function render(): string
	{
		$playerTT = $this->playerTT;
		$playerRanks = [$this->playerSeasonRank1, $this->playerSeasonRank2];
		$currentSplit = $this->currentSplit;
		$latestPatch = $this->latestPatch;
		$collapsed = $this->collapsed;

		ob_start();
		include dirname(__DIR__,2).'/resources/components/summoner-card.php';
		return ob_get_clean();
	}
}