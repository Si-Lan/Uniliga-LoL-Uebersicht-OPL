<?php

use App\Domain\Repositories\TeamInTournamentRepository;
use App\Domain\Repositories\TeamInTournamentStageRepository;
use App\Domain\Repositories\TeamRepository;
use App\Domain\Services\EntitySorter;
use App\UI\Components\Cards\SummonerCardContainer;
use App\UI\Components\Cards\TeamInTournamentCard;
use App\UI\Components\MultiOpggButton;
use App\UI\Components\Navigation\Header;
use App\UI\Components\OplOutLink;
use App\UI\Components\Team\TeamRankDisplay;
use App\UI\Page\PageMeta;

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
