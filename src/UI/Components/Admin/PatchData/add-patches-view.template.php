<?php
/** @var array<string> $patches */
/** @var array<string> $localPatchnumbers */
/** @var bool $onlyRows */

use App\UI\Components\UI\DropdownCustom;

$countPatches = 0;
?>

<?php if (!$onlyRows): ?>
    <?= new DropdownCustom("get-patches", ["new" => "neue Patches", "missing" => "fehlende Patches", "old" => "alte Patches"]) ?>
    <div class='popup-loading-indicator' style="display: none"></div>
    <div class='add-patches-display'>
    <?php endif; ?>
		<?php foreach ($patches as $patch): ?>

            <?php
            if (in_array($patch, $localPatchnumbers)) {
                $countPatches++;
                continue;
            } elseif ($countPatches > 0) {
                echo "<span>$countPatches lokale Patches</span>";
				$countPatches = 0;
            }
            ?>
            <div class='add-patches-row'>
                <span class='patch-name'><?=$patch?></span>
                <button type='button' class='add_patch' data-patch='<?=$patch?>'><span>Hinzufügen</span></button>
            </div>
		<?php endforeach; ?>
        <?php if ($countPatches > 0): ?>
            <span><?=$countPatches?> lokale Patches</span>
        <?php endif; ?>
    <?php if (!$onlyRows): ?>
    </div>
<?php endif; ?>
