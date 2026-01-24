<?php
/** @var array $items */
/** @var string $type */

use App\UI\Components\Helpers\IconRenderer;

$first_key = array_key_first($items);
?>
<div class='button-dropdown-wrapper'>
	<button type='button' class='button-dropdown' data-dropdowntype='<?=$type?>'><?=$items[$first_key]?><?= IconRenderer::getMaterialIconSpan('expand_more')?></button>
	<div class='dropdown-selection'>
		<?php foreach ($items as $data_name=>$name): ?>
			<?php $classes = implode(' ', array_filter(['dropdown-selection-item', ($data_name == $first_key) ? "selected-item" : ""])) ?>
			<button type='button' class='<?=$classes?>' data-selection='<?=$data_name?>'><?=$name?></button>
		<?php endforeach; ?>
	</div>
</div>
