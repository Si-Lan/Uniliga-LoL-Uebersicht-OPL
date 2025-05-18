<?php
/** @var mysqli $dbcn  */

use App\Components\Cards\SummonerCardContainer;
use App\Components\MultiOpggButton;
use App\Components\Navigation\Header;
use App\Components\OplOutLink;
use App\Components\Team\TeamRankDisplay;
use App\Page\PageMeta;
use App\Repositories\TeamInTournamentRepository;
use App\Repositories\TeamInTournamentStageRepository;
use App\Repositories\TeamRepository;
use App\Utilities\EntitySorter;

$teamRepo = new TeamRepository();
$teamInTournamentRepo = new TeamInTournamentRepository();
$teamInTournamentStageRepo = new TeamInTournamentStageRepository();

$team = $teamRepo->findById($_GET["team"]);

$teamInTournaments = $teamInTournamentRepo->findAllByTeam($team);
$teamInTournaments = EntitySorter::sortTeamInTournamentsByStartDate($teamInTournaments);

$pageMeta = new PageMeta($team->name, bodyClass: 'team general-team');

?>

<?= new Header() ?>

<div class='team pagetitle'>
    <?php if ($team->getLogoUrl()): ?>
        <img class='color-switch' alt src='<?= $team->getLogoUrl()?>'>
    <?php endif; ?>
	<div>
		<h2 class='pagetitle'><?= $team->name ?></h2>
		<?= new OplOutLink($team) ?>
	</div>
</div>

<main>
    <div class='team-card-list'>
		<?php foreach ($teamInTournaments as $teamInTournament): ?>
            <?php
            // TODO: Logik zur Auswahl der angezeigten Stage in neuer TeamCard Komponente regeln, sobald diese implementiert
            $teamInTournamentStages = $teamInTournamentStageRepo->findAllbyTeamInTournament($teamInTournament);
            $teamInTournamentStages = EntitySorter::sortTeamInTournamentStages($teamInTournamentStages);
			foreach ($teamInTournamentStages as $index=>$teamInTournamentStage) {
                if ($teamInTournamentStage->tournamentStage->eventType == \App\Enums\EventType::PLAYOFFS) {
                    unset($teamInTournamentStages[$index]);
                }
            }
            ?>
            <?= create_teamcard($dbcn,$team->id,end($teamInTournamentStages)->tournamentStage->id) ?>
		<?php endforeach; ?>
    </div>
	<div class='player-cards opgg-cards'>
		<div class='title'>
			<h3>Aktuelle Spieler</h3>
			<?= new MultiOpggButton($team) ?>
			<?= new TeamRankDisplay($team,true) ?>
		</div>
		<?= new SummonerCardContainer($team) ?>
	</div>
</main>
