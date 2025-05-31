<?php
use App\UI\Components\Helpers\IconRenderer;

/** @var string $id */
/** @var string $pagePopupType */
/** @var bool $dismissable */
/** @var string $content */
/** @var array $additionalClasses */

$classes = implode(' ', array_filter([$dismissable ? 'dismissable-popup' : '', $pagePopupType, $pagePopupType ? 'page-popup' : '', ...$additionalClasses]));
?>

<dialog id="<?=$id?>" class="<?=$classes?>">
	<button class="close-popup"><?=IconRenderer::getMaterialIconSpan('close')?></button>
	<div class="dialog-content">
		<?=$content?>
	</div>
</dialog>
