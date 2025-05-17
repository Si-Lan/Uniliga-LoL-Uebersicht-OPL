<?php

use App\Components\Matches\MatchButtonList;
use App\Components\Navigation\Header;
use App\Components\Navigation\TournamentNav;
use App\Components\OplOutLink;
use App\Components\Standings\StandingsTable;
use App\Components\UpdateButton;
use App\Enums\HeaderType;
use App\Page\PageMeta;
use App\Repositories\TournamentRepository;

$tournamentRepo = new TournamentRepository();

$playoffs = $tournamentRepo->findStandingsEventById($_GET["playoffs"]);

$pageMeta = new PageMeta(
        title: $playoffs->getFullName()." | ".$playoffs->rootTournament->getShortName(),
        css: ['game'],
        bodyClass: 'group'
);

?>

<?= new Header(HeaderType::TOURNAMENT,$playoffs->rootTournament) ?>

<?= new TournamentNav($playoffs->rootTournament) ?>

<div class='pagetitlewrapper withupdatebutton'>
    <div class='pagetitle'>
        <h2 class='pagetitle'><?= $playoffs->getFullName() ?></h2>
		<?= new OplOutLink($playoffs)?>
    </div>
    <?php if (!$playoffs->rootTournament->archived): ?>
		<?= new UpdateButton($playoffs) ?>
    <?php endif; ?>
</div>
<main>
    <?= new StandingsTable($playoffs) ?>
    <?= new MatchButtonList($playoffs) ?>
</main>