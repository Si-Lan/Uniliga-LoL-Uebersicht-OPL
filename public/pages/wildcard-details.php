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

$wildcard = $tournamentRepo->findStandingsEventById($_GET["wildcard"]);

$pageMeta = new PageMeta(
        title: $wildcard->getFullName()." | ".$wildcard->rootTournament->getShortName(),
        css: ['game'],
        bodyClass: 'group'
);

?>

<?= new Header(HeaderType::TOURNAMENT,$wildcard->rootTournament) ?>

<?= new TournamentNav($wildcard->rootTournament) ?>

<div class='pagetitlewrapper withupdatebutton'>
    <div class='pagetitle'>
        <h2 class='pagetitle'><?= $wildcard->getFullName() ?></h2>
        <?= new OplOutLink($wildcard) ?>
    </div>
    <?php if (!$wildcard->rootTournament->archived): ?>
        <?= new UpdateButton($wildcard) ?>
    <?php endif; ?>
</div>
<main>
    <?= new StandingsTable($wildcard) ?>
    <?= new MatchButtonList($wildcard) ?>
</main>