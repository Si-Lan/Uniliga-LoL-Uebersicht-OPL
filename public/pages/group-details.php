<?php
/** @var mysqli $dbcn  */

use App\Components\Matches\MatchButtonList;
use App\Components\Navigation\TournamentNav;
use App\Components\OplOutLink;
use App\Components\Standings\StandingsTable;
use App\Components\UpdateButton;
use App\Repositories\TournamentRepository;
use App\Utilities\UserContext;

$tournamentRepo = new TournamentRepository();

$group = $tournamentRepo->findStandingsEventById($_GET["group"]);

echo create_html_head_elements(css: ['game'], title: $group->getFullName()." | ".$group->rootTournament->getShortName()." | Uniliga LoL - Übersicht");

$open_popup = "";
if (isset($_GET['match'])) {
	$open_popup = "popup_open";
}

?>
<body class="group <?= UserContext::getLightModeClass()." $open_popup"?>">

<?= create_header(dbcn: $dbcn, title: "tournament", tournament_id: $group->rootTournament->id)?>

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
</body>