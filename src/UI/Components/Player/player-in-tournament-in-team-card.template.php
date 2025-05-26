<?php
/** @var \App\Domain\Entities\PlayerInTeamInTournament $playerInTeamInTournament */
/** @var \App\Domain\Entities\TeamInTournamentStage $teamInTournamentStage */
/** @var \App\Domain\Entities\TeamInTournamentStage|null $teamInPlayoffs */
/** @var \App\Domain\Entities\PlayerSeasonRank|null $playerSeasonRank */
/** @var \App\Domain\Entities\Patch $latestPatch */

use App\UI\Components\Helpers\IconRenderer;
use App\UI\Components\UI\PageLinkWrapper;

$tournament = $playerInTeamInTournament->teamInTournament->tournament;
$team = $playerInTeamInTournament->teamInTournament->team;
$player = $playerInTeamInTournament->player;

$tournamentSrc = $tournament->getLogoUrl() ?: '';
$tournamentImg = $tournamentSrc ? "<img class='color-switch' alt='' src='$tournamentSrc'>" : '';
$teamSrc = $playerInTeamInTournament->teamInTournament->getLogoUrl(true) ?: '';
$teamImg = $teamSrc ? "<img class='color-switch' alt='' src='$teamSrc'>" : '';

$detailAmount = count(array_filter([array_sum($playerInTeamInTournament->stats->roles),$playerInTeamInTournament->stats->champions]));

$classes = implode(' ', array_filter(['player-card', $tournament->isRunning() ? 'running-tournament' : '']))
?>

<div class="<?=$classes?>" data-details="<?=$detailAmount?>">
	<?= new PageLinkWrapper(
		href: "/turnier/{$tournament->id}",
		additionalClasses: ['player-card-div','player-card-tournament'],
		content: $tournamentImg.PageLinkWrapper::makeTarget($tournament->getSplitAndSeason(), withoutIcon: true)
	)?>

	<?= new PageLinkWrapper(
		href: $tournament->getHref()."/team/".$team->id,
		additionalClasses: ['player-card-div', 'player-card-team'],
		content: $teamImg.PageLinkWrapper::makeTarget($playerInTeamInTournament->teamInTournament->nameInTournament,true)
    )?>

	<?= new PageLinkWrapper(
		href: $teamInTournamentStage->tournamentStage->getHref(),
		additionalClasses: ['player-card-div', 'player-card-group'],
		content: PageLinkWrapper::makeTarget($teamInTournamentStage->tournamentStage->getFullName(),true)
	)?>
    <!--
    <?php if ($teamInPlayoffs !== null) : ?>
		<?= new PageLinkWrapper(
			href: $teamInPlayoffs->tournamentStage->getHref(),
			additionalClasses: ['player-card-div', 'player-card-group'],
			content: PageLinkWrapper::makeTarget($teamInPlayoffs->tournamentStage->getFullName(),true)
		)?>
    <?php endif; ?>
    -->

    <?php if ($playerSeasonRank !== null): ?>
        <div class="player-card-div player-card-rank">
            <img class="rank-emblem-mini" src="/ddragon/img/ranks/mini-crests/<?=$playerSeasonRank->rank->getRankTierLowercase()?>.svg" alt="<?=$playerSeasonRank->rank->getRankTier()?>">
			<?=$playerSeasonRank->rank->getRank()?>
        </div>
    <?php else: ?>
        <div class='player-card-div player-card-rank'>kein Rang</div>
    <?php endif; ?>

    <?php if ($playerInTeamInTournament->stats->roles !== null && array_sum($playerInTeamInTournament->stats->roles) > 0): ?>
        <div class="player-card-div player-card-roles">
            <?php foreach ($playerInTeamInTournament->stats->roles as $role=>$amount): ?>
                <?php if ($amount == 0) continue; ?>
            <div class="role-single">
                <span class="svg-wrapper role"><?= IconRenderer::getRoleIcon($role)?></span>
                <span class="played-amount"><?=$amount?></span>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($playerInTeamInTournament->stats->champions !== null && count($playerInTeamInTournament->stats->champions) > 0): ?>
        <div class="player-card-div player-card-champs">
            <?php foreach ($playerInTeamInTournament->stats->getTopChampions(5) as $champion=>$championStats): ?>
            <div class="champ-single">
                <img src="/ddragon/<?=$latestPatch->patchNumber?>/img/champion/<?=$champion?>.webp" alt="<?=$champion?>">
                <span class="played-amount"><?=$championStats['games']?></span>
            </div>
			<?php endforeach; ?>
            <?php if (count($playerInTeamInTournament->stats->champions) > 5): ?>
                <div class="champ-single"><?= IconRenderer::getMaterialIconDiv("more_horiz")?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

	<button type="button" class="player-card-div player-card-more" onclick="expand_playercard(this)">
		<?= IconRenderer::getMaterialIconDiv('expand_more') ?>
		mehr Infos
	</button>
</div>
