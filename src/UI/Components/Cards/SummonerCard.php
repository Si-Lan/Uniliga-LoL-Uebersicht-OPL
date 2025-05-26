<?php

namespace App\UI\Components\Cards;

use App\Core\Utilities\UserContext;
use App\Domain\Entities\Patch;
use App\Domain\Entities\Player;
use App\Domain\Entities\PlayerInTeam;
use App\Domain\Entities\PlayerInTeamInTournament;
use App\Domain\Entities\PlayerSeasonRank;
use App\Domain\Entities\RankedSplit;
use App\Domain\Repositories\PatchRepository;
use App\Domain\Repositories\PlayerSeasonRankRepository;
use App\Domain\Repositories\RankedSplitRepository;

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
			$rankedSplit1 = $rankedSplitRepo->findFirstSplitForTournament($playerInTeam->teamInTournament->tournament);
			$rankedSplit2 = $rankedSplitRepo->findNextSplitForTournament($playerInTeam->teamInTournament->tournament);
			$this->currentSplit = $rankedSplitRepo->findSelectedSplitForTournament($playerInTeam->teamInTournament->tournament);

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
		include __DIR__.'/summoner-card.template.php';
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}
}