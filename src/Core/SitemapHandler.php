<?php

namespace App\Core;

use App\Domain\Repositories\PlayerRepository;
use App\Domain\Repositories\TeamInTournamentRepository;
use App\Domain\Repositories\TeamRepository;
use App\Domain\Repositories\TournamentRepository;

class SitemapHandler {
	public function getSitemap(): void {
		header('Content-Type: application/xml; charset=utf-8');

		$tournamentRepo = new TournamentRepository();
		$teamInTournamentRepo = new TeamInTournamentRepository();
		$teamRepo = new TeamRepository();
		$playerRepo = new PlayerRepository();

		echo '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.PHP_EOL;

		// Startseite
		$this->addUrl('/');

		// Alle Turniere
		$tournaments = $tournamentRepo->findAllRootTournaments();
		foreach ($tournaments as $tournament) {
			$this->addUrl("{$tournament->getHref()}");
			$this->addUrl("{$tournament->getHref()}/teams");
			$this->addUrl("{$tournament->getHref()}/elo");
		}

		// Alle Turnierphasen
		foreach ($tournaments as $tournament) {
			$stages = $tournamentRepo->findAllStandingEventsByRootTournament($tournament);
			foreach ($stages as $stage) {
				$this->addUrl("{$stage->getHref()}");
			}

			$teamsInTournament = $teamInTournamentRepo->findAllByRootTournament($tournament);
			foreach ($teamsInTournament as $teamInTournament) {
				$this->addUrl("{$teamInTournament->tournament->getHref()}/team/{$teamInTournament->getSlug()}");
			}
		}

		// Alle Teams
		$teams = $teamRepo->findAll();
		foreach ($teams as $team) {
			$this->addUrl("/team/{$team->getSlug()}");
		}

		// Alle Spieler
		$players = $playerRepo->findAll();
		foreach ($players as $player) {
			$this->addUrl("/spieler/{$player->getSlug()}");
		}
		$this->addUrl("/spieler");

		echo '</urlset>';
	}

	private function addUrl(string $path): void {
		echo '<url>'.PHP_EOL;
		echo '<loc>' . htmlspecialchars("https://" . ($_SERVER['SERVER_NAME']) . $path, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</loc>'.PHP_EOL;
		echo '</url>'.PHP_EOL;
	}
}