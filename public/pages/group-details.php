<?php

use App\Domain\Repositories\TournamentRepository;
use App\UI\Components\Matches\MatchButtonList;
use App\UI\Components\Navigation\Header;
use App\UI\Components\Navigation\TournamentNav;
use App\UI\Components\OplOutLink;
use App\UI\Components\Standings\StandingsTable;
use App\UI\Components\UpdateButton;
use App\UI\Enums\HeaderType;
use App\UI\Page\PageMeta;

$tournamentRepo = new TournamentRepository();

$group = $tournamentRepo->findStandingsEventById($_GET["group"]);

$pageMeta = new PageMeta(
        title: $group->getFullName()." | ".$group->rootTournament->getShortName(),
        css: ['game'],
        bodyClass: 'group'
);

?>

<?= new Header(HeaderType::TOURNAMENT,$group->rootTournament) ?>

<?= new TournamentNav($group->rootTournament) ?>

<div class='pagetitlewrapper withupdatebutton'>
    <div class='pagetitle'>
        <h2 class='pagetitle'><?= $group->getFullName() ?></h2>
        <?= new OplOutLink($group)?>
    </div>
    <?php if (!$group->rootTournament->archived): ?>
        <?= new UpdateButton($group) ?>
    <?php endif; ?>
</div>
<main>
    <?= new StandingsTable($group) ?>
    <?= new MatchButtonList($group) ?>
</main>