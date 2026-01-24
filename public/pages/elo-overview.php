<?php

use App\Domain\Enums\EventType;
use App\Domain\Repositories\TournamentRepository;
use App\UI\Components\EloList\EloLists;
use App\UI\Components\Helpers\IconRenderer;
use App\UI\Components\Navigation\Header;
use App\UI\Components\Navigation\TournamentNav;
use App\UI\Enums\EloListView;
use App\UI\Enums\HeaderType;
use App\UI\Page\PageMeta;

$tournamentRepo = new TournamentRepository();

$tournament = $tournamentRepo->findById($_GET["tournament"]);
$leagues = $tournamentRepo->findAllByRootTournamentAndType($tournament, EventType::LEAGUE);
$wildcards = $tournamentRepo->findAllByRootTournamentAndType($tournament, EventType::WILDCARD);

$pageMeta = new PageMeta("Elo-Übersicht - {$tournament->getShortName()}", css: ['elo-rank-colors'], bodyClass: 'elo-overview');

$colored = isset($_GET['colored']);

$stage_loaded = $_REQUEST['stage'] ?? null;
if ($stage_loaded == null) {
	if (count($leagues)>0) {
		$stage_loaded = "groups";
	} elseif (count($wildcards)>0) {
        $stage_loaded = "wildcard";
    }
}

$groups_active = ($stage_loaded == "groups") ? "active" : "";
$wildcard_active = ($stage_loaded == "wildcard" && count($wildcards)>0) ? "active" : "";

$filtered = $_REQUEST['view'] ?? NULL;

?>

<?= new Header(HeaderType::TOURNAMENT, $tournament) ?>

<?= new TournamentNav($tournament, 'elo') ?>

<main>
    <h2 class='pagetitle'>Elo/Rang-Übersicht</h2>

    <div id="elolist_switch_stage_buttons" <?= (count($leagues) == 0 || count($wildcards) == 0) ? "style='display:none'" : '' ?>>
            <button type="button" class="elolist_switch_stage <?php echo $wildcard_active?>" data-stage="wildcard" data-tournament="<?= $tournament->id ?>">Wildcard-Turnier</button>
            <button type="button" class="elolist_switch_stage <?php echo $groups_active?>" data-stage="groups" data-tournament="<?= $tournament->id ?>">Gruppenphase</button>
    </div>

    <div class='searchbar'>
        <span class='material-symbol search-icon' title='Suche'><?= IconRenderer::getMaterialIcon('search') ?></span>
        <input class="search-teams-elo <?=$tournament->id?> deletable-search" oninput='search_teams_elo()' placeholder='Team suchen' type='search'>
        <button type='button' class='material-symbol search-clear' title='Suche leeren'><?= IconRenderer::getMaterialIcon('close') ?></button>
    </div>

    <div class='filter-button-wrapper'>
        <button class='filterb all-teams<?= ($filtered !== 'liga' && $filtered !== 'gruppe') ? ' active' : ''?>' onclick='switch_elo_view("<?= $tournament->id ?>","all-teams")'>Alle Ligen</button>
        <button class='filterb div-teams<?= ($filtered === 'liga') ? ' active' : ''?>' onclick='switch_elo_view("<?= $tournament->id ?>","div-teams")'>Pro Liga</button>
        <button class='filterb group-teams<?= ($filtered === 'gruppe') ? ' active' : ''?>' onclick='switch_elo_view("<?= $tournament->id ?>","group-teams")' <?= ($stage_loaded != "groups") ? "style='display: none'" : '' ?>>Pro Gruppe</button>
    </div>

    <div class='settings-button-wrapper'>
		<?php $colorByText = ($filtered === 'liga' || $filtered === 'gruppe') ? 'Rang' : 'Liga' ?>
        <button onclick='color_elo_list()'><input type='checkbox' name='coloring' <?= $colored ? 'checked' : ''?> class='controlled color-checkbox'><span>Nach <?=$colorByText?> einfärben</span></button>
    </div>

    <div class='jump-button-wrapper'<?= (($filtered == "liga" || $filtered == "gruppe") && $stage_loaded == "groups") ? '' : ' style="display: none;"'?>>
        <?php foreach ($leagues as $league): ?>
            <button onclick='jump_to_league_elo(<?=$league->number?>)'>Zu Liga <?=$league->number?></button>
        <?php endforeach; ?>
    </div>
    <div class='main-content<?= ($colored) ? " colored-list" : ""?>'>

        <?php if ($stage_loaded == "groups" && $filtered == "liga"): ?>

            <?= new EloLists($tournament, EloListView::BY_LEAGUES) ?>

        <?php elseif ($stage_loaded == "groups" && $filtered == "gruppe"): ?>

			<?= new EloLists($tournament, EloListView::BY_GROUPS) ?>

        <?php elseif ($stage_loaded == "wildcard" && $filtered == "liga"): ?>

			<?= new EloLists($tournament, EloListView::WILDCARD_BY_LEAGUES) ?>

        <?php elseif ($stage_loaded == "wildcard"): ?>

			<?= new EloLists($tournament, EloListView::WILDCARD_ALL) ?>

        <?php else: ?>

			<?= new EloLists($tournament, EloListView::ALL) ?>

        <?php endif; ?>

    </div>
    <a class='button totop' onclick='to_top()' style='opacity: 0; pointer-events: none;'><?=IconRenderer::getMaterialIconDiv('expand_less')?></a>
</main>