<?php

namespace App\Domain\Entities;

class Patch {
	private ?array $runesData = null;
	private ?array $summonerSpellData = null;
	private ?array $itemData = null;
	private ?array $championData = null;
	private string $imgUrl;
	public function __construct(
		public string $patchNumber,
		public ?bool $data = false,
		public ?bool $championWebp = false,
		public ?bool $itemWebp = false,
		public ?bool $spellWebp = false,
		public ?bool $runesWebp = false
	) {
		$this->imgUrl = "/assets/ddragon/$this->patchNumber/img";
	}

	public function allWebp(): ?bool {
		if (is_null($this->championWebp) && is_null($this->itemWebp) && is_null($this->spellWebp) && is_null($this->runesWebp)) return null;
		return $this->championWebp && $this->itemWebp && $this->spellWebp && $this->runesWebp;
	}

	public function getPatchNumberDashed(): string {
		return str_replace('.','-',$this->patchNumber);
	}

	public function getRuneUrlById(int $runeId): ?string {
		if ($this->runesData === null) {
			$this->runesData = json_decode(file_get_contents(BASE_PATH."/public/assets/ddragon/$this->patchNumber/data/runesReforged.json"),true);
		}
		foreach ($this->runesData as $runePage) {
			if ($runePage['id'] == $runeId) {
				return $this->imgUrl.'/'.str_replace('.png','.webp',$runePage['icon']);
			}
			foreach ($runePage['slots'][0]['runes'] as $keystoneRune) {
				if ($keystoneRune['id'] == $runeId) {
					return $this->imgUrl.'/'.str_replace('.png', '.webp', $keystoneRune['icon']);
				}
			}
		}
		return null;
	}
	public function getRuneNameById(int $runeId): ?string {
		if ($this->runesData === null) {
			$this->runesData = json_decode(file_get_contents(BASE_PATH."/public/assets/ddragon/$this->patchNumber/data/runesReforged.json"),true);
		}
		foreach ($this->runesData as $runePage) {
			if ($runePage['id'] == $runeId) {
				return $runePage['name'];
			}
			foreach ($runePage['slots'][0]['runes'] as $keystoneRune) {
				if ($keystoneRune['id'] == $runeId) {
					return $keystoneRune['name'];
				}
			}
		}
		return null;
	}

	public function getSummonerSpellUrlById(int $summonerSpellId): ?string {
		if ($this->summonerSpellData === null) {
			$this->summonerSpellData = json_decode(file_get_contents(BASE_PATH."/public/assets/ddragon/$this->patchNumber/data/summoner.json"),true);
			$this->summonerSpellData = array_column($this->summonerSpellData['data'],'id','key');
		}
		return $this->imgUrl.'/spell/'.$this->summonerSpellData[$summonerSpellId].'.webp';
	}

	public function getItemUrlById(int $itemId): ?string {
		return $this->imgUrl."/item/$itemId.webp";
	}
	public function getItemNameById(int $itemId): ?string {
		if ($this->itemData === null) {
			$this->itemData = json_decode(file_get_contents(BASE_PATH."/public/assets/ddragon/$this->patchNumber/data/item.json"),true);
			$this->itemData = $this->itemData['data'];
		}
		return $this->itemData[$itemId]['name'];
	}
	public function getChampionUrlById(int $championId): ?string {
		if ($this->championData === null) {
			$this->championData = json_decode(file_get_contents(BASE_PATH."/public/assets/ddragon/$this->patchNumber/data/champion.json"),true);
			$this->championData = array_column($this->championData['data'],'id','key');
		}
		return $this->imgUrl.'/champion/'.$this->championData[$championId].'.webp';
	}
}