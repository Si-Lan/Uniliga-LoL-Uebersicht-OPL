<?php
/** @var mysqli $dbcn  */

use App\Components\Helpers\IconRenderer;
use App\Components\Matches\MatchButtonList;
use App\Components\Standings\StandingsTable;
use App\Enums\EventFormat;
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

$oplUrlEvent = "https://www.opleague.pro/event/";

echo create_header(dbcn: $dbcn, title: "tournament", tournament_id: $group->rootTournament->id);

echo create_tournament_nav_buttons($group->rootTournament->id, $dbcn,"group",$league->id,$group->id);

if (!$group->rootTournament->archived) {

	$last_user_update = $dbcn->execute_query("SELECT last_update FROM updates_user_group WHERE OPL_ID_group = ?", [$group->id])->fetch_column();
	$last_cron_update = $dbcn->execute_query("SELECT last_update FROM updates_cron WHERE OPL_ID_tournament = ?", [$group->rootTournament->id])->fetch_column();

	$last_update = max($last_user_update, $last_cron_update);

	if ($last_update == NULL) {
		$updatediff = "unbekannt";
	} else {
		$last_update = strtotime($last_update);
		$currtime = time();
		$updatediff = max_time_from_timestamp($currtime - $last_update);
	}
}

if ($league->format === EventFormat::SWISS) {
	$group_title = "Swiss-Gruppe";
} else {
	$group_title = $group->getShortName();
}
?>
    <div class='pagetitlewrapper withupdatebutton'>
	    <div class='pagetitle'>
		    <h2 class='pagetitle'><?= $league->getFullName() ?> - <?= $group_title ?></h2>
                <a href='<?= $oplUrlEvent.$group->id?>' target='_blank' class='opl-link'><?= IconRenderer::getMaterialIconDiv('open_in_new') ?></a>
        </div>
    <?php
if (!$group->rootTournament->archived) {
    ?>
        <div class='updatebuttonwrapper'>
            <button type='button' class='user_update user_update_group update_data material-symbol' data-group='<?= $group->id ?>'><?= IconRenderer::getMaterialIconSpan('sync') ?></button>
			    <span class='last-update'>letztes Update:<br><?= $updatediff ?></span>
        </div>
    <?php
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