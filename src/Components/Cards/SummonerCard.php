<?php

namespace App\Components\Cards;

use App\Entities\Patch;
use App\Entities\Player;
use App\Entities\PlayerInTeam;
use App\Entities\PlayerInTeamInTournament;
use App\Entities\PlayerSeasonRank;
use App\Entities\RankedSplit;
use App\Repositories\PatchRepository;
use App\Repositories\PlayerSeasonRankRepository;
use App\Repositories\RankedSplitRepository;
use App\Utilities\UserContext;

class SummonerCard {
	private Player $player;
	private ?PlayerInTeamInTournament $playerTT=null;
	private ?PlayerSeasonRank $playerSeasonRank1=null;
	private ?PlayerSeasonRank $playerSeasonRank2=null;
	private ?RankedSplit $currentSplit=null;
	private ?Patch $latestPatch=null;
	private bool $collapsed;
	public function __construct(
		PlayerInTeam|PlayerInTeamInTournament $playerInTeam,
	) {
		if ($playerInTeam instanceof PlayerInTeamInTournament) {
			$this->playerTT = $playerInTeam;
			$this->player = $playerInTeam->player;

			$rankedSplitRepo = new RankedSplitRepository();
			$rankedSplit1 = $rankedSplitRepo->findFirstSplitForTournament($playerInTeam->tournament);
			$rankedSplit2 = $rankedSplitRepo->findNextSplitForTournament($playerInTeam->tournament);
			$this->currentSplit = $rankedSplitRepo->findSelectedSplitForTournament($playerInTeam->tournament);

			$playerSeasonRankRepo = new PlayerSeasonRankRepository();
			$this->playerSeasonRank1 = $playerSeasonRankRepo->findPlayerSeasonRank($playerInTeam->player, $rankedSplit1);
			$this->playerSeasonRank2 = ($rankedSplit2 != null) ? $playerSeasonRankRepo->findPlayerSeasonRank($playerInTeam->player, $rankedSplit2) : null;

			$patchRepo = new PatchRepository();
			$this->latestPatch = $patchRepo->findLatestPatchWithAllData();

			$this->collapsed = UserContext::summonerCardCollapsed();
		} else {
			$this->player = $playerInTeam->player;
			$this->collapsed = true;
		}
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