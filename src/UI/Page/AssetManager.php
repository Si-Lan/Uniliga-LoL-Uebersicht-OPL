<?php

namespace App\UI\Page;

class AssetManager {
	private static array $cssFiles = [];
	private static array $jsFiles = [];
	private static array $jsModuleFiles = [];
	private static array $jsModules = [];
	private static string $jsAssetDirectory = "/assets/js/";
	private static string $cssAssetDirectory = "/assets/css/";

	private static function addCssFile(string $filepath): void {
		if (!in_array($filepath, self::$cssFiles)) {
			self::$cssFiles[] = $filepath;
		}
	}
	public static function addCssAsset(string $asset): void {
		$filepath = self::$cssAssetDirectory . $asset;
		self::addCssFile($filepath);
	}

	private static function addJsFile(string $filepath): void {
		if (!in_array($filepath, self::$jsFiles)) {
			self::$jsFiles[] = $filepath;
		}
	}
	private static function addJsModuleFile(string $filepath): void {
		if (!in_array($filepath, self::$jsModuleFiles)) {
			self::$jsModuleFiles[] = $filepath;
		}
	}
	public static function addJsAsset(string $asset): void {
		$filepath = self::$jsAssetDirectory . $asset;
		self::addJsFile($filepath);
	}
	public static function addJsModuleAsset(string $asset): void {
		$filepath = self::$jsAssetDirectory . $asset;
		self::addJsModuleFile($filepath);
	}

	public static function addJsModule(string $moduleName): void {
		self::$jsModules[] = $moduleName;
	}

	public static function getCssFiles(): array {
		return self::$cssFiles;
	}
	public static function getJsFiles(): array {
		return self::$jsFiles;
	}
	public static function getJsModuleFiles(): array {
		return self::$jsModuleFiles;
	}
	public static function getJsModules(): array {
		return self::$jsModules;
	}
}