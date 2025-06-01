<?php

use App\Domain\Enums\EventType;
use App\Domain\Repositories\TeamInTournamentRepository;
use App\Domain\Repositories\TeamInTournamentStageRepository;
use App\Domain\Services\EntitySorter;
use App\UI\Components\Matches\MatchHistory;
use App\UI\Components\Navigation\Header;
use App\UI\Components\Navigation\SwitchTournamentStageButtons;
use App\UI\Components\Navigation\TeamHeaderNav;
use App\UI\Components\Navigation\TournamentNav;
use App\UI\Enums\HeaderType;
use App\UI\Page\PageMeta;

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