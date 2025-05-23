<?php

namespace App\Entities\LolGame;

class GameData {
	public string $gameVersion;
	public string $gameStart;
	public string $gameDuration;
	public bool $blueTeamWin;
	public bool $redTeamWin;
	/** @var array<GamePlayerData> */
	public array $blueTeamPlayers=[];
	/** @var array<GamePlayerData> */
	public array $redTeamPlayers=[];
	public GameTeamData $blueTeam;
	public GameTeamData $redTeam;
	public function __construct(array $matchdata) {
		$this->gameVersion = $matchdata['info']['gameVersion'];
		$this->gameStart = date('d.m.y', intval($matchdata['info']['gameCreation']/1000));
		$gameDurationSeconds = $matchdata['info']['gameDuration'] % 60;
		$gameDurationSeconds = ($gameDurationSeconds < 10) ? '0'.$gameDurationSeconds : $gameDurationSeconds;
		$this->gameDuration = floor($matchdata['info']['gameDuration'] / 60 ) . ":" . $gameDurationSeconds;
		$this->blueTeamWin = $matchdata['info']['teams'][0]['win'];
		$this->redTeamWin = $matchdata['info']['teams'][1]['win'];
		foreach ($matchdata['info']['participants'] as $index=>$participant) {
			if ($index < 5) {
				$this->blueTeamPlayers[] = new GamePlayerData($participant);
			} else {
				$this->redTeamPlayers[] = new GamePlayerData($participant);
			}
		}
		$this->blueTeamPlayers = GamePlayerData::sortPlayersByPosition($this->blueTeamPlayers);
		$this->redTeamPlayers = GamePlayerData::sortPlayersByPosition($this->redTeamPlayers);
		$this->blueTeam = new GameTeamData($matchdata['info']['teams'][0], $this->blueTeamPlayers);
		$this->redTeam = new GameTeamData($matchdata['info']['teams'][1], $this->redTeamPlayers);
	}
}