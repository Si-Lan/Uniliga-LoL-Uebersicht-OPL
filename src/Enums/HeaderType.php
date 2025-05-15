<?php

namespace App\Enums;

use App\Components\OplOutLink;
use App\Entities\Tournament;
use App\Utilities\UserContext;

enum HeaderType: string {
	case HOME = 'home';
	case TOURNAMENT = 'tournament';
	case PLAYERS = 'players';
	case MAINTENANCE = 'maintenance';
	case ADMIN = 'admin';
	case ADMIN_RGAPI = 'rgapi';
	case ADMIN_DDRAGON = 'admin_dd';
	case ADMIN_LOG = 'admin_update_log';
	case ERROR = 'error';
	case NOT_FOUND = '404';

	public function getTitle(?Tournament $tournament = null): string {
		return match($this) {
			self::HOME => 'Uniliga LoL - Übersicht',
			self::TOURNAMENT => $tournament?->getShortName().(new OplOutLink($tournament)) ?? 'Uniliga LoL - Übersicht',
			self::PLAYERS => 'Uniliga LoL - Spieler',
			self::MAINTENANCE => 'Uniliga LoL - Übersicht - Wartung',
			self::ADMIN => 'Uniliga LoL - Admin',
			self::ADMIN_RGAPI => 'Uniliga LoL - Riot-API-Daten',
			self::ADMIN_DDRAGON => 'Uniliga LoL - DDragon Updates',
			self::ADMIN_LOG => 'Uniliga LoL - Update Logs',
			self::ERROR => 'Fehler',
			self::NOT_FOUND => '404 - Nicht gefunden',
		};
	}

	public function showHomeButton(): bool {
		return !in_array($this, [self::HOME, self::MAINTENANCE]);
	}
	public function showSearchBar(): bool {
		return $this !== self::MAINTENANCE;
	}
	public function autoOpenLogin(): bool {
		return (!UserContext::isLoggedIn() && in_array($this, [self::ADMIN, self::ADMIN_DDRAGON, self::ADMIN_LOG, self::ADMIN_RGAPI]))
			|| isset($_GET["login"]);
	}
	public function passwordText(): string {
		return (isset($_GET["login"]) && isset($_POST["keypass"]) && $_POST["keypass"] != $_ENV['ADMIN_PASS']) ? 'Falsches Passwort' : '';
	}
}
