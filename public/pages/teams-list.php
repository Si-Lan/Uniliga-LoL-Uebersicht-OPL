<?php

use App\Domain\Entities\TeamInTournamentStage;
use App\Domain\Entities\Tournament;
use App\Domain\Enums\EventType;
use App\Domain\Repositories\TeamInTournamentStageRepository;
use App\Domain\Repositories\TournamentRepository;
use App\Domain\Services\EntitySorter;
use App\UI\Components\Helpers\IconRenderer;
use App\UI\Components\Navigation\Header;
use App\UI\Components\Navigation\TournamentNav;
use App\UI\Components\Popups\Popup;
use App\UI\Components\UI\PageLink;
use App\UI\Enums\HeaderType;
use App\UI\Page\PageMeta;

$tournamentRepo = new TournamentRepository();
$teamInTournamentStageRepo = new TeamInTournamentStageRepository();

$tournament = $tournamentRepo->findById($_GET['tournament']);

$pageMeta = new PageMeta("Team-Liste - {$tournament->getShortName()}", bodyClass: 'teamlist');

echo new Header(HeaderType::TOURNAMENT, $tournament);

echo new TournamentNav($tournament,'teamlist');

// Turniere, Gruppen und Wildcards für die Filter holen
$leagues = $tournamentRepo->findAllByRootTournamentAndType($tournament, EventType::LEAGUE);
/** @var array<int, Tournament> $indexedLeagues */
$indexedLeagues = [];
foreach ($leagues as $league) {
    $indexedLeagues[$league->id] = $league;
}
$groups = $tournamentRepo->findAllGroupsByRootTournament($tournament);
/** @var array<int, Tournament> $indexedGroups */
$indexedGroups = [];
foreach ($groups as $group) {
    $indexedGroups[$group->id] = $group;
}
$wildcards = $tournamentRepo->findAllByRootTournamentAndType($tournament, EventType::WILDCARD);
/** @var array<int, Tournament> $indexedWildcards */
$indexedWildcards = [];
foreach ($wildcards as $wildcard) {
    $indexedWildcards[$wildcard->id] = $wildcard;
}

// Aktuell gefilterete Ligen/Gruppen holen
$filteredLeagueId = filter_var($_GET['liga']??null, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
$filteredByLeague = array_key_exists($filteredLeagueId??'', $indexedLeagues) || array_key_exists($filteredLeagueId??'', $indexedWildcards);

$filteredGroupId = filter_var($_GET['gruppe']??null, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
$filteredByGroup = $filteredByLeague && (array_key_exists($filteredGroupId??'', $indexedGroups) || array_key_exists($filteredLeagueId??'', $indexedGroups));
if (array_key_exists($filteredLeagueId??'', $indexedGroups)) $filteredGroupId = $filteredLeagueId;
$filteredGroupId = array_key_exists($filteredLeagueId??'', $indexedGroups) ? $filteredLeagueId : $filteredGroupId;
$filteredGroupIsLeague = array_key_exists($filteredLeagueId??'', $indexedGroups);

?>

<main>
    <h2 class="pagetitle">Team-Liste</h2>

    <div class="searchbar">
		<span class="material-symbol search-icon" title="Suche">
            <?= IconRenderer::getMaterialIcon('search') ?>
		</span>
        <input class="search-teams deletable-search" onkeyup='search_teams()' placeholder="Teams durchsuchen" type="search">
        <button class="material-symbol search-clear" title="Suche leeren">
			<?= IconRenderer::getMaterialIcon('close') ?>
        </button>
    </div>

    <div class="team-filter-wrap">
        <h3>Filter</h3>
        <div class="slct div-select-wrap">
            <select name='Ligen' class='divisions' onchange='filter_teams_list_division(this.value)'>
                <option value='all' <?= $filteredByLeague ? '' : 'selected="selected"' ?>>Alle Ligen</option>
                <?php foreach ([...$leagues,...$wildcards] as $league): ?>
                    <option value="<?=$league->id?>" <?= $filteredLeagueId === $league->id ? 'selected="selected"' : '' ?> <?=$league->isEventWithStanding() ? 'class="standings_league"' : ''?>><?=$league->getShortName()?></option>
                <?php endforeach; ?>
            </select>
            <?= IconRenderer::getMaterialIconSpan('arrow_drop_down')?>
        </div>
        <div class='slct groups-select-wrap'>
            <select name='Gruppen' class='groups' onchange='filter_teams_list_group(this.value)'>
                <option value='all' <?= ($filteredByGroup && !$filteredGroupIsLeague) ? '' : 'selected="selected"' ?>>Alle Gruppen</option>
                <?php if ($filteredByLeague): ?>
                    <?php foreach ($groups as $group): ?>
                        <?php if ($group->getDirectParentTournament()->id != $filteredLeagueId) continue; ?>
                        <option value="<?=$group->id?>" <?= $filteredGroupId === $group->id ? 'selected="selected"' : '' ?>><?=$group->getShortName()?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
			<?= IconRenderer::getMaterialIconSpan('arrow_drop_down')?>
        </div>
        <?= new PageLink(
                href: "/turnier/$tournament->id/gruppe/$filteredGroupId",
                text: 'Zur Gruppe',
                additionalClasses: ['button', 'b-group', ($filteredByGroup)?'shown':'']
        )?>
    </div>

    <div class='team-list <?=$tournament->id?>'>
        <div class='no-search-res-text <?=$tournament->id?>' style='display: none'>Kein Team gefunden!</div>

        <?php
        // Alle Teams im Turnier holen (Nach Gruppen in denen sie spielen)
        $teamsInGroups = $teamInTournamentStageRepo->findAllInGroupStageByRootTournament($tournament);
        $teamsInGroups = EntitySorter::sortTeamInTournamentStages($teamsInGroups);
        /** @var array<int, array<TeamInTournamentStage>> $indexedTeams */
        $indexedTeams = [];

        foreach ($teamsInGroups as $teamInGroup) {
            if (!isset($indexedTeams[$teamInGroup->team->id])) {
                $indexedTeams[$teamInGroup->team->id] = [];
            }
            $indexedTeams[$teamInGroup->team->id][] = $teamInGroup;
        }

        $teamsInWildcards = $teamInTournamentStageRepo->findAllWildcardsByRootTournament($tournament);
        /** @var array<int, array<TeamInTournamentStage>> $indexedTeamsInWildcards */
        $indexedTeamsInWildcards = [];
        foreach ($teamsInWildcards as $teamInWildcard) {
            if (!isset($indexedTeams[$teamInWildcard->team->id])) {
                $indexedTeams[$teamInWildcard->team->id] = [];
            }
            $indexedTeams[$teamInWildcard->team->id][] = $teamInWildcard;

            if (!isset($indexedTeamsInWildcards[$teamInWildcard->team->id])) {
                $indexedTeamsInWildcards[$teamInWildcard->team->id] = [];
            }
            $indexedTeamsInWildcards[$teamInWildcard->team->id][] = $teamInWildcard;
        }

        uasort( $indexedTeams, function (array $a, array $b) {
            return strtolower($a[0]->teamInRootTournament->nameInTournament) <=> strtolower($b[0]->teamInRootTournament->nameInTournament);
        });
        ?>

        <?php foreach ($indexedTeams as $teamInTournamentStages): ?>
            <?php // Team-Button erstellen
            $leagueIds = [];
            foreach ($teamInTournamentStages as $teamInTournamentStage) {
                if ($teamInTournamentStage->tournamentStage->eventType === EventType::GROUP) {
                    $leagueIds[] = $teamInTournamentStage->tournamentStage->getDirectParentTournament()->id;
                } else {
                    $leagueIds[] = $teamInTournamentStage->tournamentStage->id;
                }
            }
            $leagueIds = array_unique($leagueIds);

            $groupIds = [];
            foreach ($teamInTournamentStages as $teamInTournamentStage) {
                if ($teamInTournamentStage->tournamentStage->eventType === EventType::GROUP) {
                    $groupIds[] = $teamInTournamentStage->tournamentStage->id;
                }
            }

            // Aktuellstes/höchstes TeamInTournamentStage auswählen
            $teamInTournamentStage = $teamInTournamentStages[0];

            $teamPopup = new Popup(
                    id: $teamInTournamentStage->team->id,
                    pagePopupType: 'team-popup',
                    dismissable: true
            );
            \App\UI\Page\AssetManager::addJsAsset('components/pagePopups.js');
            ?>

            <?php $classes = implode(' ', array_filter(['team-button', ($filteredByLeague && !in_array($filteredLeagueId, $leagueIds)) ? "filterD-off" : "", ($filteredByGroup && !in_array($filteredGroupId, $groupIds)) ? "filterG-off" : ""])); ?>
            <button class="<?= $classes ?>" data-league='<?= implode(' ', $leagueIds)?>' data-group='<?=implode(' ', $groupIds)?>' data-team-id='<?=$teamInTournamentStage->team->id?>' data-tournament-id='<?=$tournament->id?>' data-dialog-id="<?=$teamPopup->getId()?>">
                <?php if ($teamInTournamentStage->teamInRootTournament->getLogoUrl()): ?>
                    <img class='color-switch' alt src='<?= $teamInTournamentStage->teamInRootTournament->getLogoUrl() ?>'>
                <?php endif; ?>
                <span>
                    <span class="team-name"><?= $teamInTournamentStage->teamInRootTournament->nameInTournament?></span>
                    <span class="team-group"><?= $teamInTournamentStage->tournamentStage->getFullName() ?></span>
                </span>
            </button>

            <?= $teamPopup->render() ?>
        <?php endforeach; ?>

    </div>
</main>