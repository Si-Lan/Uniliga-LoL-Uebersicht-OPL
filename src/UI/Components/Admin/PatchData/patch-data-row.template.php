<?php
/** @var Patch $patch */

use App\Domain\Entities\Patch;
use App\UI\Components\Admin\PatchData\PatchDataRowDetails;
use App\UI\Components\Helpers\IconRenderer;
use App\UI\Components\Popups\Popup;

$popupContent = new PatchDataRowDetails($patch);
$popup = new Popup(id: "patch_options_{$patch->getPatchNumberDashed()}", noCloseButton: true, content: $popupContent->render(), additionalClasses: ["patch-more-popup"]);
?>

<div class='patch-row' data-patch='<?=$patch->patchNumber?>'>
	<span class='patch-name'><?=$patch->patchNumber?></span>
	<div class='patch-updatebutton-wrapper'>
		<div class='patchdata-status json' data-status='<?=$patch->data?>' data-patch='<?=$patch->patchNumber?>'></div>
		<button type='button' class='patch-update json' data-patch='<?=$patch->patchNumber?>'><span>JSONs</span></button>
	</div>
	<div class='patch-updatebutton-wrapper'>
		<div class='patchdata-status all-img' data-status='<?=$patch->allWebp()?>' data-patch='<?=$patch->patchNumber?>'></div>
		<button type='button' class='patch-update all-img' data-getimg='all' data-patch='<?=$patch->patchNumber?>'><span>Bilder</span></button>
	</div>
	<button type='button' class='patch-more-options' data-dialog-id="<?= $popup->getId() ?>"><span>einzelne Bilder</span></button>
	<?= $popup->render() ?>
    <button type="button" title="Löschen" class="patch-delete" data-patch="<?=$patch->patchNumber?>" style="padding: 0 12px"><?= IconRenderer::getMaterialIconSpan('delete')?></button>
</div>