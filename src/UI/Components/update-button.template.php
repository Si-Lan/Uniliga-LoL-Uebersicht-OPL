<?php
/** @var string $updatediff */
/** @var string $htmlDataString */
/** @var string $htmlClassString */

use App\UI\Components\Helpers\IconRenderer;

?>
<div class='updatebuttonwrapper'>
	<button type='button' class='user_update <?= $htmlClassString ?> update_data' <?= $htmlDataString ?>><?= IconRenderer::getMaterialIconSpan('sync') ?></button>
	<span class='last-update'>letztes Update:<br><span class="update-time"><?= $updatediff ?></span></span>
</div>
