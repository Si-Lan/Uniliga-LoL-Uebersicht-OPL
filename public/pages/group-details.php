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