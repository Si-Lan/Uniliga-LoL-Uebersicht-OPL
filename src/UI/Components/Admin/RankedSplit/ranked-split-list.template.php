<?php
/** @var array<RankedSplit $rankedSplits */

use App\Domain\Entities\RankedSplit;
use App\UI\Components\Admin\RankedSplit\RankedSplitRow;
use App\UI\Components\Helpers\IconRenderer;
?>

<div class="ranked-split-list">
	<?php foreach ($rankedSplits as $rankedSplit): ?>
	<?= new RankedSplitRow($rankedSplit) ?>
	<?php endforeach; ?>
	<div class="button-row">
		<button type="button" class="add_ranked_split">
			<?= IconRenderer::getMaterialIconDiv('add') ?>
			Hinzufügen
		</button>
		<button type="button" class="save_ranked_split_changes">
			<?= IconRenderer::getMaterialIconDiv('save') ?>
			Änderungen speichern
		</button>
	</div>
</div>
