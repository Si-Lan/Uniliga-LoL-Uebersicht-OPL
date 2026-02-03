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

$playoffs = $tournamentRepo->findStandingsEventById($_GET["playoffs"]);

$pageMeta = new PageMeta(
        title: $playoffs->getFullName()." | ".$playoffs->rootTournament->getShortName(),
        bodyClass: 'group',
        bodyDataId: $playoffs->id
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
    <?php if ($playoffs->isEventWithEliminationBracket()): ?>
        <?= new EliminationBracket($playoffs)?>
    <?php else: ?>
        <?= new StandingsTable($playoffs) ?>
        <?= new MatchButtonList($playoffs) ?>
    <?php endif; ?>
</main>