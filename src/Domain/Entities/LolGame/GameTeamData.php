<?php

namespace App\Domain\Entities\LolGame;

class GameTeamData {
	public int $goldEarned = 0;
	public bool $win;
	public int $kills = 0;
	public int $deaths = 0;
	public int $assists = 0;
	public array $bans;
	public int $towers = 0;
	public int $inhibs = 0;
	public int $heralds = 0;
	public int $dragons = 0;
	public int $barons = 0;
	public int $atakhans = 0;
	public int $grubs = 0;

	/**
	 * @param array $teamData
	 * @param array<GamePlayerData> $gamePlayers
	 */
	public function __construct(array $teamData, public array $gamePlayers) {
		foreach ($gamePlayers as $gamePlayer) {
			$this->goldEarned += $gamePlayer->goldEarned;
			$this->kills += $gamePlayer->kills;
			$this->deaths += $gamePlayer->deaths;
			$this->assists += $gamePlayer->assists;
		}
		$this->win = $teamData['win'];
		$this->bans = $teamData['bans'];
		$this->bans = array_column($this->bans,'championId');
		$this->towers = $teamData['objectives']['tower']['kills'];
		$this->inhibs = $teamData['objectives']['inhibitor']['kills'];
		$this->heralds = $teamData['objectives']['riftHerald']['kills'];
		$this->dragons = $teamData['objectives']['dragon']['kills'];
		$this->barons = $teamData['objectives']['baron']['kills'];
		$this->atakhans = $teamData['objectives']['atakhan']['kills']??0;
		$this->grubs = $teamData['objectives']['horde']['kills']??0;
	}

	public function getGoldEarnedFormatted(): string {
		return floor($this->goldEarned/1000).'.'.floor($this->goldEarned % 1000 / 100).'k';
	}
}