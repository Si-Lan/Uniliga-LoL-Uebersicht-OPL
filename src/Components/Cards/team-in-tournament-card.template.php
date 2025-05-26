<?php
/** @var \App\Entities\TeamInTournamentStage $teamInTournamentStage */
/** @var \App\Entities\TeamSeasonRankInTournament $teamSeasonRankInTournament */
/** @var array<\App\Entities\PlayerInTeamInTournament> $playersInTeamInTournament */

use App\Components\Helpers\IconRenderer;
use App\Components\UI\PageLinkWrapper;

$tournamentSrc = $teamInTournamentStage->tournamentStage->rootTournament->getLogoUrl() ?: '';
$tournamentImg = $tournamentSrc ? "<img class='color-switch' alt='' src='$tournamentSrc'>" : '';
$teamSrc = $teamInTournamentStage->teamInRootTournament->getLogoUrl(true) ?: '';
$teamImg = $teamSrc ? "<img class='color-switch' alt='' src='$teamSrc'>" : '';

$classes = implode(' ', array_filter(['team-card', $teamInTournamentStage->tournamentStage->rootTournament->isRunning() ? 'running-tournament' : '']))
?>

<div class="<?=$classes?>">
    <?= new PageLinkWrapper(
            href: "/turnier/{$teamInTournamentStage->tournamentStage->rootTournament->id}",
            additionalClasses: ['team-card-div','team-card-tournament'],
            content: $tournamentImg.PageLinkWrapper::makeTarget($teamInTournamentStage->tournamentStage->rootTournament->getSplitAndSeason(), withoutIcon: true)
    )?>

    <?= new PageLinkWrapper(
            href: $teamInTournamentStage->tournamentStage->getHref(),
            additionalClasses: ['team-card-div', 'team-card-league'],
            content: PageLinkWrapper::makeTarget($teamInTournamentStage->tournamentStage->getFullName(),true)
    )?>

    <?= new PageLinkWrapper(
            href: $teamInTournamentStage->tournamentStage->rootTournament->getHref()."/team/".$teamInTournamentStage->team->id,
            additionalClasses: ['team-card-div','team-card-teampage'],
            content: $teamImg.PageLinkWrapper::makeTarget($teamInTournamentStage->teamInRootTournament->nameInTournament,true)
    )?>

    <?php if ($teamInTournamentStage->standing !== null): ?>
        <div class="team-card-div team-card-standings">
            <?= $teamInTournamentStage->standing?>. Platz : <?= $teamInTournamentStage->getWinsLosses() ?>
        </div>
    <?php endif; ?>

    <?php if ($teamSeasonRankInTournament !== null): ?>
        <div class="team-card-div team-card-rank">
            <img class="rank-emblem-mini" src="/ddragon/img/ranks/mini-crests/<?=$teamSeasonRankInTournament->rank->getRankTierLowercase() ?>.svg" alt="">
            <?= $teamSeasonRankInTournament->rank->getRank() ?>
        </div>
    <?php endif; ?>

    <button type="button" class="team-card-div team-card-playeramount">
        <?= IconRenderer::getMaterialIconSpan('person')?>
		<?= count($playersInTeamInTournament) ?> Spieler
		<?= IconRenderer::getMaterialIconSpan('expand_more')?>
	</button>

	<div class="team-card-div team-card-players-wrapper">
		<div class="team-card-players">
            <?php foreach ($playersInTeamInTournament as $playerInTeamInTournament): ?>
                <?php
                $roleHtml = '';
                arsort($playerInTeamInTournament->stats->roles);
                if (array_sum($playerInTeamInTournament->stats->roles) > 0) {
					$roles = '';
                    foreach ($playerInTeamInTournament->stats->roles as $role=>$amount) {
                        if ($amount == 0) continue;
                        $roles .= '<span class="svg-wrapper role">'.IconRenderer::getRoleIcon($role).'</span>';
                    }
                    $roleHtml = "<div class='team-card-players-roles'>$roles</div>";
                }
                ?>
                <?= new PageLinkWrapper(
                        href: "/spieler/{$playerInTeamInTournament->player->id}",
                        content: $roleHtml.PageLinkWrapper::makeTarget($playerInTeamInTournament->player->name, true)
                )?>
            <?php endforeach; ?>
		</div>
	</div>
</div>
