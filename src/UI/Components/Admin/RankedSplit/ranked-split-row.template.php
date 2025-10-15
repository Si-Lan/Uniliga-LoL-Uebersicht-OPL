<?php
/** @var RankedSplit $rankedSplit */

use App\Domain\Entities\RankedSplit;
use App\UI\Components\Helpers\IconRenderer;

$newRow = $rankedSplit === null;
?>

<div class="<?= implode(" ", ["ranked-split-edit", $newRow ? "ranked_split_write" : ""]) ?>">
	<label class="write_ranked_split_season">
		Season
		<input type="text" name="season" value="<?= $newRow ? '' : $rankedSplit->season ?>" <?= $newRow ? '' : 'readonly' ?>>
	</label>
	<label class="write_ranked_split_split">
		Split
		<input type="text" name="split" value="<?= $newRow ? '' : $rankedSplit->split ?>" <?= $newRow ? '' : 'readonly' ?>>
	</label>
	<label class="write_ranked_split_startdate">
		Start
		<input type="date" name="split_start" value="<?= $newRow ? '' : $rankedSplit->dateStart->format('Y-m-d') ?>">
	</label>
	<label class="write_ranked_split_enddate">
		Ende
		<input type="date" name="split_end" value="<?= $newRow ? '' : $rankedSplit->dateEnd?->format('Y-m-d') ?? '' ?>">
	</label>

	<?php if (!$newRow): ?>
		<button type="button" class="sec-button reset_inputs" title="Zurücksetzen"><?= IconRenderer::getMaterialIconDiv("restart")?></button>
		<button type="button" class="sec-button delete_ranked_split" title="Löschen"><?= IconRenderer::getMaterialIconDiv("delete")?></button>
	<?php else: ?>
		<button type="button" class="sec-button save_ranked_split" title="Speichern"><?= IconRenderer::getMaterialIconDiv("save")?></button>
		<button type="button" class="sec-button cancel_ranked_split" title="Abbrechen"><?= IconRenderer::getMaterialIconDiv("close")?></button>
	<?php endif; ?>
</div>