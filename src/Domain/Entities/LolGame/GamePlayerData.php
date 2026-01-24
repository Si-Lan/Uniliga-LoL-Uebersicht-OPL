<?php

namespace App\Domain\Entities\LolGame;

use App\Domain\Entities\Player;

class GamePlayerData {
	public string $puuid;
	public string $riotIdName;
	public string $riotIdTag;
	public string $summonerName;
	public string $championName;
	public string $championId;
	public int $championLevel;
	public string $teamPosition;
	public int $goldEarned;
	public bool $win;
	public int $kills;
	public int $deaths;
	public int $assists;
	public int $totalMinionsKilled;
	public int $KeystoneRuneId;
	public int $secondaryRunePageId;
	public int $summoner1Id;
	public int $summoner2Id;
	public array $itemIds = [];
	public function __construct(array $playerData) {
		$this->puuid = $playerData['puuid'];
		$this->riotIdName = $playerData['riotIdGameName']??'';
		$this->riotIdTag = $playerData['riotIdTagline']??'';
		$this->summonerName = $playerData['summonerName']??'';
		$this->championName = $playerData['championName'];
		$this->championId = $playerData['championId'];
		$this->championLevel = $playerData['champLevel'];
		$this->teamPosition = $playerData['teamPosition'];
		$this->goldEarned = $playerData['goldEarned'];
		$this->win = $playerData['win'];
		$this->kills = $playerData['kills'];
		$this->deaths = $playerData['deaths'];
		$this->assists = $playerData['assists'];
		$this->totalMinionsKilled = $playerData['totalMinionsKilled'];

		$this->KeystoneRuneId = $playerData['perks']['styles'][0]['selections'][0]['perk'];
		$this->secondaryRunePageId = $playerData['perks']['styles'][1]['style'];
		$this->summoner1Id = $playerData['summoner1Id'];
		$this->summoner2Id = $playerData['summoner2Id'];

		for ($i = 0; $i <= 6; $i++) {
			$this->itemIds[] = ($playerData['item'.$i] == 0) ? 7050 : $playerData['item'.$i];
		}
	}

	public function getGoldEarnedFormatted(): string {
		return floor($this->goldEarned/1000).'.'.floor($this->goldEarned % 1000 / 100).'k';
	}

	/**
	 * @param array<Player> $players
	 * @return ?Player
	 */
	public function findMatchingPlayer(array $players): ?Player {
		foreach ($players as $player) {
			if ($player->puuid == $this->puuid) {
				return $player;
			}
		}
		return null;
	}

	/**
	 * @param array<GamePlayerData> $gamePlayersData
	 * @return array<GamePlayerData>
	 */
	public static function sortPlayersByPosition(array $gamePlayersData): array {
		$rolesPrio = array("TOP"=>0,"JUNGLE"=>1,"MIDDLE"=>2,"BOTTOM"=>3,"UTILITY"=>4, ""=>5);

		usort($gamePlayersData, function (GamePlayerData$a, GamePlayerData $b) use ($rolesPrio) {
			return $rolesPrio[$a->teamPosition] <=> $rolesPrio[$b->teamPosition];
		});

		return $gamePlayersData;
	}
}