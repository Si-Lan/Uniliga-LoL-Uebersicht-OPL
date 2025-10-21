<?php
/** @var string $placeholder */
/** @var array $options */
/** @var array $selectedOptions */


use App\UI\Components\Helpers\IconRenderer;
?>

<div class="multi-select-dropdown">
	<button class="multi-select-header">
            <span class="multi-select-header-text">
                <span class="multi-select-header-placeholder"><?=$placeholder?></span>
				<?php foreach ($selectedOptions as $option): ?>
				<span class="multi-select-header-selection" data-selection="<?=$option?>"><?=$option?></span>
				<?php endforeach; ?>
            </span>
		<?= IconRenderer::getMaterialIconSpan('expand_more') ?>
	</button>
	<div class="multi-select-options">
		<?php foreach ($options as $option): ?>
		<label>
			<input type="checkbox" value="<?=$option?>" <?= (in_array($option, $selectedOptions) ? "checked" : "") ?>>
			<?=$option?>
		</label>
		<?php endforeach; ?>
	</div>
</div>
