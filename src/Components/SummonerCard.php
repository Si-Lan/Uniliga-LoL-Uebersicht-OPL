<?php

namespace App\Components;

use App\Repository\PatchRepository;
use App\Repository\PlayerSeasonRankRepository;
use App\Repository\TournamentRepository;
use App\Repository\PlayerInTeamInTournamentRepository;
use App\Repository\RankedSplitRepository;
use App\Entity\PlayerInTeamInTournament;
use App\Entity\PlayerSeasonRank;
use App\Entity\RankedSplit;
use App\Entity\Patch;

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
		$this->currentSplit = $rankedSplitRepo->getSelectedSplitForTournament($tournament);

		$playerTTRepo = new PlayerInTeamInTournamentRepository();
		$this->playerTT = $playerTTRepo->findByPlayerAndTeamAndTournament($playerID, $teamID, $tournamentID);

		$playerSeasonRankRepo = new PlayerSeasonRankRepository();
		$this->playerSeasonRank1 = $playerSeasonRankRepo->findByPlayerAndSeasonAndSplit($playerID, $rankedSplit1->season, $rankedSplit1->split);
		$this->playerSeasonRank2 = ($rankedSplit2 != null) ? $playerSeasonRankRepo->findByPlayerAndSeasonAndSplit($playerID, $rankedSplit2->season, $rankedSplit2->split) : null;

		$patchRepo = new PatchRepository();
		$this->latestPatch = $patchRepo->getLatestPatchWithAllData();

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