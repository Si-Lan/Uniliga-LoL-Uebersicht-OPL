<?php
/** @var mysqli $dbcn  */

use App\Components\Helpers\IconRenderer;
use App\Components\Matches\MatchButtonList;
use App\Components\Standings\StandingsTable;
use App\Components\UpdateButton;
use App\Enums\EventType;
use App\Repositories\TournamentRepository;
use App\Utilities\UserPreferences;

$tournamentRepo = new TournamentRepository();

$group = $tournamentRepo->findStandingsEventById($_GET["group"]);

if ($group->eventType === EventType::LEAGUE) {
    $league = $group;
} else {
	$league = $group->directParentTournament;
}

echo create_html_head_elements(css: ['game'], title: $group->getFullName()." | ".$group->rootTournament->getShortName()." | Uniliga LoL - Übersicht");

$open_popup = "";
if (isset($_GET['match'])) {
	$open_popup = "popup_open";
}

?>
<body class="group <?= UserPreferences::getLightModeClass()." $open_popup"?>">
<?php

echo create_header(dbcn: $dbcn, title: "tournament", tournament_id: $group->rootTournament->id);

echo create_tournament_nav_buttons($group->rootTournament->id, $dbcn,"group",$league->id,$group->id);

?>
    <div class='pagetitlewrapper withupdatebutton'>
	    <div class='pagetitle'>
		    <h2 class='pagetitle'><?= $group->getFullName() ?></h2>
                <a href='https://www.opleague.pro/event/<?= $group->id?>' target='_blank' class='opl-link'><?= IconRenderer::getMaterialIconDiv('open_in_new') ?></a>
        </div>
    <?php
if (!$group->rootTournament->archived) {
    echo new UpdateButton($group);
}
?>
    </div>
    <main>
        <?php
    echo new StandingsTable($group);
    echo new MatchButtonList($group);
    ?>
    </main>
</body>