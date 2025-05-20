<?php

use App\Components\Cards\SummonerCardContainer;
use App\Components\Cards\TeamInTournamentCard;
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
            <?= new TeamInTournamentCard($teamInTournament) ?>
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
