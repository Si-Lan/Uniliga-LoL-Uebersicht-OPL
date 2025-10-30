<?php

use App\UI\Components\Admin\PatchData\AddPatchesView;
use App\UI\Components\Admin\PatchData\PatchDataRows;
use App\UI\Components\Navigation\Header;
use App\UI\Components\Popups\Popup;
use App\UI\Enums\HeaderType;
use App\UI\Page\PageMeta;

$pageMeta = new PageMeta('DDragon Updates',bodyClass: 'admin');

echo new Header(HeaderType::ADMIN_DDRAGON);

?>
<div class="patch-display">
    <dialog class='patch-result-popup dismissable-popup'>
        <div class='dialog-content'></div>
    </dialog>
    <div class="patch-table">
        <div class="patch-header">
            <button type="button" class="open_add_patch_popup" data-dialog-id="add-patch-popup"><span>Patch hinzufügen</span></button>
            <?= new Popup(id: "add-patch-popup", noCloseButton: true, content: new AddPatchesView(), additionalClasses: ["add-patch-popup"]) ?>
            <button type="button" class="sync_patches"><span>Patches synchronisieren</span></button>
        </div>
        <div class="get-patch-options">
            <input type="checkbox" id="force-overwrite-patch-img" name="force-overwrite-patch-img">
            <label for="force-overwrite-patch-img">Alle Bilder herunterladen und überschreiben erzwingen</label>
        </div>
        <?= new PatchDataRows() ?>
    </div>
</div>