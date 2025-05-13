<?php
/** @var string $opggUrl */
/** @var int $playerAmount */
use App\Components\Helpers\IconRenderer;
?>

<a href='<?= $opggUrl ?>' class='button op-gg' target='_blank'>
	<div class='svg-wrapper op-gg'><?= IconRenderer::getOPGGIcon()?></div>
	<span class='player-amount'>(<?= $playerAmount ?> Spieler)</span>
</a>
