<?php

use App\Components\Matches\MatchHistory;
use App\Components\Navigation\Header;
use App\Components\Navigation\SwitchTournamentStageButtons;
use App\Components\Navigation\TeamHeaderNav;
use App\Components\Navigation\TournamentNav;
use App\Enums\EventType;
use App\Enums\HeaderType;
use App\Page\PageMeta;
use App\Repositories\TeamInTournamentRepository;
use App\Repositories\TeamInTournamentStageRepository;
use App\Utilities\EntitySorter;

$teamInTournamentRepo = new TeamInTournamentRepository();
$teamInTournamentStageRepo = new TeamInTournamentStageRepository();

$teamInTournament = $teamInTournamentRepo->findByTeamIdAndTournamentId($_GET["team"], $_GET["tournament"]);

// alle Gruppen / Wildcard-Turniere / Playoffs, in denen das Team spielt, holen und sortieren
$teamInTournamentStages = $teamInTournamentStageRepo->findAllbyTeamInTournament($teamInTournament);
$teamInTournamentStages = EntitySorter::sortTeamInTournamentStages($teamInTournamentStages);

// Über Routing ist bereits klar, dass Team und Turnier existieren, hier wird noch geprüft, ob das Team auch im Turnier spielt
if (count($teamInTournamentStages) === 0) {
	trigger404("team-in-tournament");
	exit();
}

// initial neueste Stage auswählen, die nicht Playoffs ist
$teamInTournamentStage = end($teamInTournamentStages);
foreach ($teamInTournamentStages as $teamInTournamentStageInLoop) {
	if ($teamInTournamentStageInLoop->tournamentStage->eventType !== EventType::PLAYOFFS) {
		$teamInTournamentStage = $teamInTournamentStageInLoop;
	}
}

$pageMeta = new PageMeta(
        title: "$teamInTournament->nameInTournament - Matchhistory | {$teamInTournament->tournament->getShortName()}",
        css: ['game'],
        bodyClass: 'match-history'
);

?>

<?= new Header(HeaderType::TOURNAMENT, $teamInTournament->tournament)?>

<?= new TournamentNav($teamInTournament->tournament)?>

<?= new TeamHeaderNav($teamInTournamentStage->teamInRootTournament, "matchhistory") ?>

<main>
    <?= new SwitchTournamentStageButtons($teamInTournamentStages, $teamInTournamentStage) ?>

    <?= new MatchHistory($teamInTournamentStage->teamInRootTournament,$teamInTournamentStage->tournamentStage) ?>
</main>