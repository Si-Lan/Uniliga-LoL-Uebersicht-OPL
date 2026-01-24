<?php
/** @var $oplUrl */
/** @var $entityId */

use App\UI\Components\Helpers\IconRenderer;

?>
<a href='<?= $oplUrl ?><?= $entityId?>' target='_blank' class='opl-link'><?= IconRenderer::getMaterialIconDiv('open_in_new') ?></a>
