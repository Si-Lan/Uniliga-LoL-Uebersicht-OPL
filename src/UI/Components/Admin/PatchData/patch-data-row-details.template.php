<?php
/** @var Patch $patch */

use App\Domain\Entities\Patch;
?>

<span class='patch-name'>patch <?= $patch->patchNumber ?></span>
<div class='patch-row'>
	<div class='patch-updatebutton-wrapper'>
		<div class='patchdata-status champion-img' data-status='<?= $patch->championWebp ?>' data-patch='<?= $patch->patchNumber ?>'></div>
		<button type='button' class='patch-update' data-getimg='champions' data-patch='<?= $patch->patchNumber ?>'><span>Champions</span></button>
	</div>
	<div class='patch-updatebutton-wrapper'>
		<div class='patchdata-status item-img' data-status='<?= $patch->itemWebp ?>' data-patch='<?= $patch->patchNumber ?>'></div>
		<button type='button' class='patch-update' data-getimg='items' data-patch='<?= $patch->patchNumber ?>'><span>Items</span></button>
	</div>
	<div class='patch-updatebutton-wrapper'>
		<div class='patchdata-status spell-img' data-status='<?= $patch->spellWebp ?>' data-patch='<?= $patch->patchNumber ?>'></div>
		<button type='button' class='patch-update' data-getimg='summoners' data-patch='<?= $patch->patchNumber ?>'><span>Summoners</span></button>
	</div>
	<div class='patch-updatebutton-wrapper'>
		<div class='patchdata-status runes-img' data-status='<?= $patch->runesWebp ?>' data-patch='<?= $patch->patchNumber ?>'></div>
		<button type='button' class='patch-update' data-getimg='runes' data-patch='<?= $patch->patchNumber ?>'><span>Runes</span></button>
	</div>
</div>