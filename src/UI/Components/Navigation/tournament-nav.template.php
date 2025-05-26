<?php
/** @var \App\Domain\Entities\Tournament $tournament */
/** @var \App\Domain\Entities\RankedSplit|null $nextSplit */
/** @var string $activeTab */

use App\UI\Components\Helpers\IconRenderer;

?>

<nav class='turnier-bonus-buttons'>
	<div class='turnier-nav-buttons'>
        <?php $classes = implode(' ', array_filter(['button', ($activeTab === 'overview')?'active':'']))?>
		<a href='/turnier/<?= $tournament->id?>' class='<?=$classes?>'>
            <?= IconRenderer::getMaterialIconDiv('sports_esports')?>
			Turnier
		</a>
		<?php $classes = implode(' ', array_filter(['button', ($activeTab === 'teamlist')?'active':'']))?>
		<a href='/turnier/<?= $tournament->id?>/teams' class='<?=$classes?>'>
			<?= IconRenderer::getMaterialIconDiv('group')?>
			Teams
		</a>
		<?php $classes = implode(' ', array_filter(['button', ($activeTab === 'elo')?'active':'']))?>
		<a href='/turnier/<?= $tournament->id?>/elo' class='<?=$classes?>'>
			<?= IconRenderer::getMaterialIconDiv('stars')?>
			Eloverteilung
		</a>
	</div>

	<div class='ranked-settings-wrapper'>
		<button type='button' class='ranked-settings'>
			<span><?= $tournament->userSelectedRankedSplit?->getName(trailingZero: false)?></span>
			<img src='/ddragon/img/ranks/emblems/unranked.webp' alt='Rank-Einstellungen'>
		</button>
		<div class='ranked-settings-popover'>
			<span>Angezeigter Rang</span>
			<div>
				<input type='radio' id='ranked-split-radio-1' value='<?=$tournament->rankedSplit?->getName()?>' name='ranked-split' data-tournament='<?= $tournament->id?>' <?= $tournament->userSelectedRankedSplit?->equals($tournament->rankedSplit)?'checked':'' ?>>
				<label for='ranked-split-radio-1'><?=$tournament->rankedSplit?->getPrettyName()?></label>
			</div>
            <?php if ($nextSplit):?>
			<div>
				<input type='radio' id='ranked-split-radio-2' value='<?=$nextSplit->getName()?>' name='ranked-split' data-tournament='<?= $tournament->id?>' <?= $tournament->userSelectedRankedSplit?->equals($nextSplit)?'checked':'' ?>>
				<label for='ranked-split-radio-2'><?=$nextSplit->getPrettyName()?></label>
			</div>
            <?php endif;?>
		</div>
	</div>
</nav>