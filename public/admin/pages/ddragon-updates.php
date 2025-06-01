<?php
/** @var mysqli $dbcn  */

use App\UI\Components\Navigation\Header;
use App\UI\Enums\HeaderType;
use App\UI\Page\AssetManager;
use App\UI\Page\PageMeta;

$pageMeta = new PageMeta('DDragon Updates',bodyClass: 'admin');
AssetManager::addJsFile('/admin/scripts/main.js');

echo new Header(HeaderType::ADMIN_DDRAGON);

?>
<div class="patch-display">
    <dialog class='patch-result-popup dismissable-popup'>
        <div class='dialog-content'></div>
    </dialog>
    <div class="patch-table">
        <div class="patch-header">
            <button type="button" class="open_add_patch_popup"><span>Patch hinzufügen</span></button>
            <button type="button" class="sync_patches"><span>Patches synchronisieren</span></button>
            <dialog class="add-patch-popup dismissable-popup">
                <div class="dialog-content">
                    <?= create_dropdown("get-patches",["new"=>"neue Patches","missing"=>"fehlende Patches","old"=>"alte Patches"]) ?>
                    <div class='popup-loading-indicator' style="display: none"></div>
                    <div class='add-patches-display'>
                        <?= create_add_patch_view($dbcn, "new")?>
                    </div>
                </div>
            </dialog>
        </div>
        <div class="get-patch-options">
            <input type="checkbox" id="force-overwrite-patch-img" name="force-overwrite-patch-img">
            <label for="force-overwrite-patch-img">Alle Bilder herunterladen und überschreiben erzwingen</label>
        </div>
        <?= generate_patch_rows($dbcn) ?>
    </div>
</div>