<?php
use App\UI\Components\Helpers\IconRenderer;

/** @var string $id */
/** @var string $pagePopupType */
/** @var bool $dismissable */
/** @var bool $noCloseButton */
/** @var bool $autoOpen */
/** @var string $content */
/** @var array $additionalClasses */

$classes = implode(' ', array_filter([$dismissable ? 'dismissable-popup' : '', $pagePopupType, $pagePopupType ? 'page-popup' : '', $autoOpen?'modalopen_auto':'', ...$additionalClasses]));
?>

<dialog id="<?=$id?>" class="<?=$classes?>">
    <?php if (!$noCloseButton): ?>
	<button class="close-popup"><?=IconRenderer::getMaterialIconSpan('close')?></button>
    <?php endif; ?>
	<div class="dialog-content">
		<?=$content?>
	</div>
</dialog>
