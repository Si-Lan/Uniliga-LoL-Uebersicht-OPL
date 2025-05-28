<?php

use App\Domain\Entities\TeamInTournamentStage;
use App\Domain\Entities\TeamSeasonRankInTournament;
use App\Domain\Entities\Tournament;
use App\UI\Components\EloList\EloListRow;
use App\UI\Enums\EloListView;

/** @var Tournament $tournament */
/** @var EloListView $view */
/** @var array<TeamInTournamentStage> $teamsInTournamentStages */
/** @var array<int, array<string, TeamSeasonRankInTournament>> $teamSeasonRankMap */
?>

<?php $classes = implode(' ', array_filter(['teams-elo-list', $view->getClassName()])); ?>
<div class='<?=$classes?>'>
    <?php $class = ($view == EloListView::BY_LEAGUES || $view == EloListView::BY_GROUPS) ? "class='liga{$tournament->getLeadingLeagueNumber()}'" : ''?>
	<h3 <?=$class?>><?=$view->getHeading($tournament)?></h3>
    <div class='elo-list-row elo-list-header'>
        <div class='elo-list-pre-header league'>Liga #</div>
        <div class='elo-list-item-wrapper-header'>
            <div class='elo-list-item team'>Team</div>
            <div class='elo-list-item rank'>avg. Rang</div>
            <div class='elo-list-item elo-nr'>Elo</div>
        </div>
    </div>
    <?php foreach ($teamsInTournamentStages as $index=>$teamInTournamentStage) : ?>
        <?php if ($index != 0): ?>
            <div class="divider-light"></div>
        <?php endif; ?>
        <?= new EloListRow($teamInTournamentStage, $teamSeasonRankMap[$teamInTournamentStage->team->id][$teamInTournamentStage->teamInRootTournament->tournament->userSelectedRankedSplit->getName()], $view) ?>
    <?php endforeach; ?>
</div>