<?php
/** @var array<\App\Domain\Entities\PlayerInTeamInTournament> $playerInTeamsInTournaments */
/** @var \App\Domain\Entities\Player $player */
/** @var bool $showPlayerPageLink */

use App\UI\Components\Helpers\IconRenderer;
use App\UI\Components\OplOutLink;
use App\UI\Components\Player\PlayerInTournamentInTeamCard;
use App\UI\Components\UI\PageLink;

?>

<?php if ($showPlayerPageLink): ?>
<?= new PageLink(
	href: "/spieler/{$player->id}",
	text: 'Zur Spielerseite',
	materialIcon: 'person'
)?>
<?php endif; ?>

<div class="player-ov-titlewrapper">
	<h2 class="player-ov-name"><?= $player->name ?></h2>
	<?= new OplOutLink($player) ?>
</div>
<div class="divider-light"></div>

<?php if ($player->riotIdName): ?>
<div class="player-ov-riotid-wrapper">
	<a class="player-ov-riotid tooltip page-link" href="https://op.gg/summoner/euw/<?= $player->getEncodedRiotID() ?>" target="_blank">
		<span class="league-icon"><?= IconRenderer::getLeagueIcon()?></span>
		<span><?= $player->getFullRiotID() ?></span>
		<span class="tooltiptext linkinfo">
			<?= IconRenderer::getMaterialIconSpan('open_in_new') ?>
			OP.GG
		</span>
	</a>
	<?php if ($player->rank->rankTier !== null): ?>
		<div class="player-rank"><img class='rank-emblem-mini' src='/assets/ddragon/img/ranks/mini-crests/<?=$player->rank->getRankTierLowercase()?>.svg' alt='<?=$player->rank->getRankTier()?>'><?=$player->rank->getRank()?></div>
	<?php else: ?>
		<div class="player-rank">kein Rang</div>
	<?php endif; ?>
</div>
<?php endif; ?>

<?php if (count($playerInTeamsInTournaments) >= 2): ?>
	<div class="player-ov-buttons">
		<button type='button' class='expand-pcards' title='Ausklappen' data-action="expand">
			<?= IconRenderer::getMaterialIconDiv('unfold_more') ?>
		</button>
		<button type='button' class='expand-pcards' title='Einklappen' data-action="collapse">
			<?= IconRenderer::getMaterialIconDiv('unfold_less') ?>
		</button>
	</div>
<?php endif; ?>

<div class="player-popup-content">
	<?php foreach ($playerInTeamsInTournaments as $playerInTeamInTournament): ?>
		<?= new PlayerInTournamentInTeamCard($playerInTeamInTournament) ?>
	<?php endforeach; ?>
</div>