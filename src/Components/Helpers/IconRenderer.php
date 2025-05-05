<?php

namespace App\Components\Helpers;

class IconRenderer {
	protected const string ICON_PATH = BASE_PATH.'/public/icons/';
	protected const string MATERIAL_ICON_PATH = BASE_PATH.'/public/icons/material/';
	protected const string IMG_PATH = BASE_PATH.'/public/img/';
	protected const string ROLE_ICON_PATH = BASE_PATH.'/public/ddragon/img/positions/';
	protected const string RANK_ICON_PATH = BASE_PATH.'/public/ddragon/img/ranks/mini-crests/';

	public static function getMaterialIcon(string $name): string
	{
		$path = self::MATERIAL_ICON_PATH . $name . '.svg';
		if (!file_exists($path)) {
			return "";
		}
		return file_get_contents($path);
	}
	public static function getMaterialIconDiv(string $name): string {
		$path = self::MATERIAL_ICON_PATH . $name . '.svg';
		if (!file_exists($path)) {
			return "<div class='material-symbol'></div>";
		}
		return "<div class='material-symbol'>".file_get_contents($path)."</div>";
	}
	public static function getMaterialIconSpan(string $name): string {
		$path = self::MATERIAL_ICON_PATH . $name . '.svg';
		if (!file_exists($path)) {
			return "<span class='material-symbol'></span>";
		}
		return "<span class='material-symbol'>".file_get_contents($path)."</span>";
	}

	public static function getRankIcon(string $rank): string {
		$path = self::RANK_ICON_PATH . $rank . '.svg';
		if (!file_exists($path)) {
			return "";
		}
		return file_get_contents($path);
	}
	public static function getRoleIcon(string $role): string {
		$path = self::ROLE_ICON_PATH . 'position-' . $role . '-light.svg';
		if (!file_exists($path)) {
			return "";
		}
		return file_get_contents($path);
	}

	public static function getLeagueIcon(): string {
		return file_get_contents(self::ICON_PATH.'LoL_Icon_Flat.svg');
	}
	public static function getOPGGIcon(): string {
		return file_get_contents(self::IMG_PATH.'opgglogo.svg');
	}
	public static function getGithubIcon(): string {
		return file_get_contents(self::IMG_PATH.'github-mark-white.svg');
	}

}