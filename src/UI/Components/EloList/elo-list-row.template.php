<?php
use App\Domain\Entities\TeamInTournamentStage;
use App\Domain\Entities\TeamSeasonRankInTournament;
use App\Domain\Enums\EventType;
use App\UI\Components\Helpers\IconRenderer;
use App\UI\Components\Popups\Popup;
use App\UI\Enums\EloListView;

/** @var TeamInTournamentStage $teamInTournamentStage */
/** @var TeamSeasonRankInTournament $teamSeasonRankInTournament */
/** @var EloListView $view */
$tournamentStage = $teamInTournamentStage->tournamentStage;
$team = $teamInTournamentStage->team;
$teamLogoSrc = $teamInTournamentStage->teamInRootTournament->getLogoUrl(true);
$teamLogoHtml = $teamLogoSrc ? "<img class='color-switch' src='{$teamLogoSrc}' alt='Teamlogo'>" : "<img class='color-switch' src='data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs%3D' alt='Teamlogo'>";

$teamPopup = new Popup($team->id, "team-popup", dismissable: true);
?>

<?php $classes = implode(' ', array_filter(['elo-list-row', 'elo-list-team', $view->isLeagueColored() ? "liga{$tournamentStage->getLeadingLeagueNumber()}" : "rank".floor($teamSeasonRankInTournament->rank->rankNum??0)])) ?>
<div class="<?= $classes ?>" data-teamid="<?= $team->id ?>">
	<div class="elo-list-pre league">
        <?= ($tournamentStage->eventType == EventType::WILDCARD) ? 'Wc' : ''?>
		<?= $tournamentStage->getLeagueName() ?>
	</div>
	<div class="elo-list-item-wrapper">
		<button type="button" class="elo-list-item team page-link" data-team-id="<?=$team->id?>" data-tournament-id="<?=$tournamentStage->getRootTournament()->id?>" data-dialog-id="<?=$teamPopup->getId()?>">
            <?= $teamLogoHtml ?>
			<span class="page-link-target">
				<span class="team-name">
					<?=$teamInTournamentStage->teamInRootTournament->nameInTournament ?>
				</span>
				<span class="material-symbol page-link-icon popup-icon">
                    <?= IconRenderer::getMaterialIcon('ad_group') ?>
				</span>
			</span>
		</button>

		<div class="elo-list-item rank">
            <?php if ($teamSeasonRankInTournament->hasRank()): ?>
                <img class="rank-emblem-mini" src="/assets/ddragon/img/ranks/mini-crests/<?=$teamSeasonRankInTournament->rank->getRankTierLowercase()?>.svg" alt="<?=$teamSeasonRankInTournament->rank->getRankTier()?>">
                <span><?=$teamSeasonRankInTournament->rank->getRank()?></span>
            <?php endif; ?>
		</div>

		<div class="elo-list-item elo-nr">
			<span>(<?=round($teamSeasonRankInTournament->rank->rankNum??0, 2)?>)</span>
		</div>
	</div>
</div>
<?= $teamPopup->render()?>
