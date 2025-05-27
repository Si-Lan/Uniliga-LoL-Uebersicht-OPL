<?php
use App\Domain\Entities\Player;
use App\UI\Components\Helpers\IconRenderer;

/** @var Player $player */
/** @var bool $removeFromRecents */
?>

<div class="player-ov-card-wrapper">
	<button class="player-ov-card" type="button" onclick="popup_player(<?=$player->id?>,true)">
		<span>
			<?= IconRenderer::getMaterialIconSpan('person') ?>
			<?= $player->name ?>
		</span>
		<?php if ($player->riotIdName !== null): ?>
			<div class="divider"></div>
			<span>
				<span class="league-icon"><?= IconRenderer::getLeagueIcon() ?></span>
				<?= $player->getFullRiotID() ?>
			</span>
		<?php endif; ?>
	</button>
	<?php if ($removeFromRecents): ?>
		<a class="x-remove-recent-player" href="/spieler" onclick="remove_recent_player(<?=$player->id?>)">
			<?= IconRenderer::getMaterialIconDiv('close') ?>
		</a>
	<?php endif; ?>
</div>