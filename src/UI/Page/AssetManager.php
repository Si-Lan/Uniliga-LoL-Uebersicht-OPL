<?php

namespace App\UI\Page;

class AssetManager {
	private static array $cssFiles = [];
	private static array $jsFiles = [];
	public static function addCssFile(string $filepath): void {
		if (!in_array($filepath, self::$cssFiles)) {
			self::$cssFiles[] = $filepath;
		}
	}
	public static function addJsFile(string $filepath): void {
		if (!in_array($filepath, self::$jsFiles)) {
			self::$jsFiles[] = $filepath;
		}
	}
	public static function getCssFiles(): array {
		return self::$cssFiles;
	}
	public static function getJsFiles(): array {
		return self::$jsFiles;
	}
}