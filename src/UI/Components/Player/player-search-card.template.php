<?php
use App\Domain\Entities\Player;
use App\UI\Components\Helpers\IconRenderer;
use App\UI\Components\Popups\Popup;

/** @var Player $player */
/** @var bool $removeFromRecents */

$playerPopup = new Popup($player->id, "player-popup", dismissable: true);
?>

<div class="player-ov-card-wrapper">
	<button class="player-ov-card" type="button" data-player-id="<?=$player->id?>" data-dialog-id="<?=$playerPopup->getId()?>">
		<span><?= IconRenderer::getMaterialIconSpan('person') ?><?= $player->name ?></span>
		<?php if ($player->riotIdName !== null): ?>
			<div class="divider"></div>
			<span>
				<span class="league-icon"><?= IconRenderer::getLeagueIcon() ?></span>
				<?= $player->getFullRiotID() ?>
			</span>
		<?php endif; ?>
	</button>
	<?php if ($removeFromRecents): ?>
		<a class="x-remove-recent-player" href="/spieler" data-playerid="<?=$player->id?>">
			<?= IconRenderer::getMaterialIconDiv('close') ?>
		</a>
	<?php endif; ?>

    <?= $playerPopup->render() ?>
</div>