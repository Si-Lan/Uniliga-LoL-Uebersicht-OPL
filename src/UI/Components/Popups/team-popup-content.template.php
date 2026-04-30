<?php
/** @var \App\Domain\Entities\TeamInTournament $teamInTournament */

use App\UI\Components\Cards\SummonerCardContainer;
use App\UI\Components\MultiOpggButton;
use App\UI\Components\OplOutLink;
use App\UI\Components\Team\TeamRankDisplay;
use App\UI\Components\UI\PageLink;
use App\UI\Components\UI\SummonerCardCollapseButton;

?>

<div class="team-title">
    <?php if ($teamInTournament->getLogoUrl()): ?>
        <img class="list-overview-logo" src="<?=$teamInTournament->getLogoUrl()?>" alt="Team-Logo">
    <?php endif; ?>
	<div>
		<h2>
			<?= new PageLink(
				href: "/team/{$teamInTournament->team->getSlug()}",
				text: $teamInTournament->nameInTournament,
                linkIcon: ''
			)?>
		</h2>
        <?= new OplOutLink($teamInTournament->team)?>
	</div>
</div>

<?= new PageLink(
        href: "{$teamInTournament->tournament->getHref()}/team/{$teamInTournament->getSlug()}",
        text: "Team-Übersicht"
)?>

<div class="sc-buttons opgg-cards">
    <?= new MultiOpggButton($teamInTournament)?>
    <?= new SummonerCardCollapseButton()?>
</div>

<?= new TeamRankDisplay($teamInTournament,true) ?>

<?= new SummonerCardContainer($teamInTournament) ?>

