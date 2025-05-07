<?php

namespace App\Components\Helpers;

class IconRenderer {
	protected const string ICON_PATH = BASE_PATH.'/public/icons/';
	protected const string MATERIAL_ICON_PATH = BASE_PATH.'/public/icons/material/';
	protected const string IMG_PATH = BASE_PATH.'/public/img/';
	protected const string ROLE_ICON_PATH = BASE_PATH.'/public/ddragon/img/positions/';
	protected const string RANK_ICON_PATH = BASE_PATH.'/public/ddragon/img/ranks/mini-crests/';

	private static function createMaterialSymbolClasses(array $additionalClasses): string {
		return implode(' ', array_filter(["material-symbol", ...$additionalClasses]));
	}

	public static function getMaterialIcon(string $name): string {
		$path = self::MATERIAL_ICON_PATH . $name . '.svg';
		return file_exists($path) ? file_get_contents($path) : '';
	}
	private static function getWrappedMaterialIcon(string $name, string $tag = 'span', ?array $addClasses = []): string {
		$classes = self::createMaterialSymbolClasses($addClasses);
		$content = self::getMaterialIcon($name);
		return "<$tag class='$classes'>$content</$tag>";
	}
	public static function getMaterialIconDiv(string $name, ?array $addClasses = []): string {
		return self::getWrappedMaterialIcon($name, 'div', $addClasses);
	}
	public static function getMaterialIconSpan(string $name, ?array $addClasses = []): string {
		return self::getWrappedMaterialIcon($name, 'span', $addClasses);
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