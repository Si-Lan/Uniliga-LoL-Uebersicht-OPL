<?php

use App\Domain\Repositories\TournamentRepository;
use App\UI\Components\EliminationBrackets\EliminationBracket;
use App\UI\Components\Matches\MatchButtonList;
use App\UI\Components\Navigation\Header;
use App\UI\Components\Navigation\TournamentNav;
use App\UI\Components\OplOutLink;
use App\UI\Components\Standings\StandingsTable;
use App\UI\Components\UpdateButton;
use App\UI\Enums\HeaderType;
use App\UI\Page\PageMeta;

$tournamentRepo = new TournamentRepository();

$wildcard = $tournamentRepo->findStandingsEventById($_GET["wildcard"]);

$pageMeta = new PageMeta(
        title: $wildcard->getFullName()." | ".$wildcard->rootTournament->getShortName(),
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
    <?php if ($wildcard->isEventWithEliminationBracket()): ?>
        <?= new EliminationBracket($wildcard) ?>
    <?php else: ?>
        <?= new StandingsTable($wildcard) ?>
        <?= new MatchButtonList($wildcard) ?>
    <?php endif; ?>
</main>